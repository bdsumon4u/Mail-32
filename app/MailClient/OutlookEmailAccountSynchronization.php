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

use App\Models\EmailAccountFolder;

class OutlookEmailAccountSynchronization extends EmailAccountIdBasedSynchronization
{
    /**
     * The delta meta key
     */
    const DELTA_META_KEY = 'deltaLink';

    /**
     * Mode for the sync process
     *
     * @var string chill|force
     */
    protected $mode = self::FORCE_MODE;

    /**
    * Start account messages synchronization
    *
    * @return void
    */
    public function syncMessages()
    {
        $folders  = $this->account->folders->active();
        $messages = $this->getInitialDataAndQueueMessagesForDelete($folders);

        foreach ($folders as $folder) {
            if (array_key_exists($folder->id, $messages)) {
                $this->info(sprintf(
                    $this->isInitialSync($folder)
                    ? 'Performing initial sync for folder %s.'
                    : 'Performing sync for folder %s via delta link.',
                    $folder->name
                ));

                $this->processMessages($messages[$folder->id]);
            }
        }
    }

    /**
     * Get all messages and queue deleted messages
     * so we can use the messages from the queue to handle moved messages
     *
     * @param \Illuminate\Support\Collection $folders
     *
     * @return array
     */
    protected function getInitialDataAndQueueMessagesForDelete($folders)
    {
        $data = [];

        foreach ($folders as $folder) {
            $remoteFolder = $this->findFolder($folder);

            // @todo - https://github.com/microsoftgraph/msgraph-sdk-php/issues/68
            $messages = $remoteFolder->getDeltaMessages(
                $folder->getMeta(static::DELTA_META_KEY), // current delta link
                    $this->account->initial_sync_from->format('Y-m-d H:i:s')
            );

            // Store the delta link here as the collection is modified below
            $newDeltaLink = $messages->deltaLink;

            // Trash and spam folders are not synced on the initial sync
            if (! $this->account->isInitialSyncPerformed() && $remoteFolder->isTrashOrSpam()) {
                $folder->setMeta(static::DELTA_META_KEY, $newDeltaLink);

                continue;
            }

            // Make the messages unique based on their ID as Microsoft
            // does not guarantee that the messages in delta will be unique
            // As we are batch getting each message
            $messages = $messages->unique(fn ($message) => $message->getId())->values();

            // Queue messages for removal first and remove it from the messages
            // collection as we don't need them
            if (! $this->isInitialSync($folder)) {
                $messages = $this->checkForRemovedMessages($messages, $folder);
            }

            // We need to get all changes messages via batch so we can perform full
            // update to the message in case the message exists in database
            // The function also will retrieve openExtensions and the headers e.q. references
            // and in-reply to because it's not possible to retieve the headers via delta as
            // Microsoft does not return them for sent messages
            $data[$folder->id] = $this->getImapClient()->batchGetMessages($messages);

            // And after processing update the folder delta link
            // In case of failures to catch the messages again
            // Because we are checking if the message exists in database
            // In this case, no duplicate messages will be created
            $folder->setMeta(static::DELTA_META_KEY, $newDeltaLink);
        }

        return $data;
    }

    /**
     * Handle any removed messages via delta
     *
     * Removed messages can exists only when fetching the data via deltaLink
     *
     * @param \Illuninate\Support\Collection $messages
     * @param \App\Models\EmailAccountFolder $folder
     *
     * @return \Illuminate\Support\Collection
     */
    protected function checkForRemovedMessages($messages, EmailAccountFolder $folder)
    {
        return $messages->filter(function ($message) use ($folder) {
            if ($message->isRemoved()) {
                $this->addMessageToDeleteQueue($message->getId(), $folder);

                return false;
            }

            return true;
        })->values();
    }

    /**
     * Check whether the sync is initial one
     * The check is performed based on the delta link
     *
     * @param \App\Models\EmailAccountFolder $folder
     *
     * @return boolean
     */
    protected function isInitialSync(EmailAccountFolder $folder) : bool
    {
        return is_null($folder->getMeta(static::DELTA_META_KEY));
    }
}
