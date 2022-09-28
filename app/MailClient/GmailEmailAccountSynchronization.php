<?php
/**
 * Concord CRM - https://www.concordcrm.com
 *
 * @version   1.0.7
 *
 * @link      Releases - https://www.concordcrm.com/releases
 * @link      Terms Of Service - https://www.concordcrm.com/terms
 *
 * @copyright Copyright (c) 2022-2022 KONKORD DIGITAL
 */

namespace App\MailClient;

use Google_Service_Exception;
use Illuminate\Support\LazyCollection;

class GmailEmailAccountSynchronization extends EmailAccountIdBasedSynchronization
{
    /**
     * The history meta key
     */
    const HISTORY_META_KEY = 'historyId';

    /**
     * Mode for the sync process
     *
     * @var string chill|force
     */
    protected $mode = self::FORCE_MODE;

    /**
     * Limit the Gmail messages collection
     *
     * @var int
     */
    protected int $limit = 1000;

    /**
     * Start account messages synchronization
     *
     * @return void
     */
    public function syncMessages()
    {
        foreach ($this->account->folders->active() as $folder) {
            if ($currentHistoryId = $folder->getMeta(static::HISTORY_META_KEY)) {
                $this->syncFromHistoryId($currentHistoryId, $folder);

                continue;
            }

            $this->syncAll($folder);
        }
    }

    /**
     * Sync account via Gmail history id data
     *
     * @param  int  $currentHistoryId
     * @param  \App\Models\EmailAccountFolder  $folder
     * @return void
     */
    protected function syncFromHistoryId($currentHistoryId, $folder)
    {
        $this->info(sprintf(
            'Performing sync for folder %s via history id.',
            $folder->name
        ));

        $messages = collect([]);
        $nextPage = null;
        $deleted = [];

        try {
            do {
                /**
                 * @var \Google_Service_Gmail_ListHistoryResponse
                 */
                $historyList = $this->getImapClient()->getHistory($currentHistoryId, [
                    'maxResults' => $this->limit,
                    'pageToken' => $nextPage,
                    'labelId' => $folder->remote_id,
                ]);

                foreach ($historyList->getHistory() as $history) {
                    // First handle all removed messages
                    // Remove them from database so we can fetch all messages
                    // below in a batch and perform create/update
                    foreach ($history->getMessagesDeleted() as $message) {
                        $deleted[] = $messageId = $message->getMessage()->getId();

                        $this->deleteMessage($messageId);
                    }

                    foreach (['MessagesAdded', 'LabelsAdded', 'LabelsRemoved'] as $method) {
                        $messages = $messages->merge($history->{'get'.$method}());
                    }
                }

                // We need to get the History ID in the first batch
                // so we can know up to which point the sync has been done for this user.
                if (! isset($newHistoryId)) {
                    $newHistoryId = $historyList->getHistoryId();
                }
            } while (($nextPage = $historyList->getNextPageToken()));
        } catch (Google_Service_Exception $e) {
            /*
            * A historyId is typically valid for at least a week, but in some rare circumstances may be valid
            * for only a few hours.
            *
            * If you receive an HTTP 404 error response, your application should perform a full sync.
            *
            * @link https://developers.google.com/gmail/api/v1/reference/users/history/list#startHistoryId
            */
            if ($e->getCode() == 404) {
                return $this->syncAll($folder);
            }
        }

        // Update/create for messages
        // Handles all three methods, messagesAded, labelsAdded, labelsRemoved
        $filtered = $messages->reject(
            fn ($history) => in_array($history->getMessage()->getId(), $deleted)
        )
            // The messages may be duplicated multiple times in the Google history data
            ->unique(fn ($history) => $history->getMessage()->getId())
            ->map(fn ($history) => $history->getMessage())->values();

        // After we make the messages unique
        // We will fetch each message via batch request so we can perform
        // update or insert with the new data
        // The batch will also check for any messages which are not found
        // and will remove them from the array
        $this->processMessages(
            $this->excludeSystemMailables($this->getImapClient()->batchGetMessages($filtered))
        );

        if (isset($newHistoryId)) {
            $folder->setMeta(static::HISTORY_META_KEY, $newHistoryId);
        }
    }

    /**
     * Sync all account messages
     *
     * @param  \App\Models\EmailAccountFolder  $folder
     * @return void
     */
    protected function syncAll($folder)
    {
        $remoteFolder = $this->findFolder($folder);
        // Trash and spam folders are not synced on the initial sync
        // But we need to get the first history id from the first message so
        // we can store the history id in database as it was synced
        if ($remoteFolder->isTrashOrSpam()) {
            $messages = $this->getInitialMessages($remoteFolder, 1);
            $this->setFolderHistoryIdFromMessage($folder, $messages->first());

            return;
        }

        $this->info(sprintf(
            'Performing initial sync for folder %s.',
            $folder->name
        ));

        $callback = function () use ($remoteFolder) {
            $next = [];
            do {
                $result = $next ? $next : $this->getInitialMessages($remoteFolder);

                foreach ($result->all() as $message) {
                    yield $message;
                }
            } while ($next = $remoteFolder->nextPage());

            return;
            yield;
        };

        $messages = LazyCollection::make($callback);

        // Remember the first message as we will set the history id
        // after the messages are processed and the system mailables excluded
        $firstMessage = $messages->first();

        $this->processMessages($this->excludeSystemMailables($messages));
        $this->setFolderHistoryIdFromMessage($folder, $firstMessage);
    }

    /**
     * Get the initial messages for the for sync
     *
     * @param  \App\Innoclapps\Contracts\MailClient\FolderInterface  $folder
     * @param  null|int  $limit
     * @return \Illuminate\Support\Collection
     */
    protected function getInitialMessages($folder, $limit = null)
    {
        return $folder->getMessagesFrom(
            $this->account->initial_sync_from->format('Y-m-d H:i:s'),
            $limit ?? $this->limit
        );
    }

    /**
     * We need to get the History ID from the very first existing message
     * so we can know up to which point the sync has been done for this folder.
     *
     * The the database folder history id from the provided message
     * In all cases, the provided message should be the first message
     *
     * @param  \App\Models\EmailAccountFolder  $folder
     * @param  \App\Innoclapps\Contracts\MailClient\MessageInterface|null  $message
     * @return void
     */
    protected function setFolderHistoryIdFromMessage($folder, $message)
    {
        if (is_null($message)) {
            return;
        }

        $folder->setMeta(static::HISTORY_META_KEY, $message->getHistoryId());
    }

    /**
     * Exclude the mailables which are sent from the system notifications
     *
     * @param  \Illuminate\Support\Collection  $messages
     * @return \Illuminate\Support\Collection
     */
    protected function excludeSystemMailables($messages)
    {
        return $messages->filter(fn ($message) => is_null($message->getHeader('X-Hotash-Mailable')))->values();
    }
}
