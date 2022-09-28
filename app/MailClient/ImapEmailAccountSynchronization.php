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

use App\Enums\SyncState;
use Illuminate\Support\Str;
use Ddeboer\Imap\Exception\UnexpectedEncodingException;
use Ddeboer\Imap\Exception\UnsupportedCharsetException;
use App\MailClient\Exceptions\SyncFolderTimeoutException;

class ImapEmailAccountSynchronization extends EmailAccountSynchronization
{
    /**
     * Indiates the remote folder last uid
     */
    const SKIPPED_LAST_UID_META_KEY = 'sync-skipped-last-uid';

    /**
     *  Maximum number of uidinvalidity errors in a row.
     */
    const MAX_UIDINVALID_RESYNCS = 5;

    /**
    * Start account messages synchronization
    *
    * @throws \App\MailClient\Exceptions\SyncFolderTimeoutException
    *
    * @return void
    */
    public function syncMessages()
    {
        $this->checkForRemovedMessages();

        foreach ($this->account->folders->active() as $folder) {
            try {
                $this->retrieveAndProcess($folder);
            } catch (SyncFolderTimeoutException $e) {
                $this->error($e->getMessage());
            }

            if ($this->isTimeout()) {
                $this->error(sprintf('Synchronization interrupted by timeout after %s folder.', $folder->name));

                break;
            }
        }
    }

    /**
     * Sync account messages
     *
     * @param \App\Models\EmailAccountFolder $folder
     *
     * @return void
     */
    protected function retrieveAndProcess($folder)
    {
        $lastUid = $this->messages->getLastUidByForImapAccountByFolder($folder->id);

        if (! $lastUid) {
            // There is no lastuid, in this case, try to see if the folder
            // is skipped e.q. for spam and trash and sync the new messages
            // from the stored last uid
            $lastUid = $folder->getMeta(self::SKIPPED_LAST_UID_META_KEY);
        }

        $remoteFolder = $this->findFolder($folder);

        if (! $this->checkSelectable($remoteFolder)) {
            return;
        }

        // Trash and spam folders are not synced on the initial sync,
        // we will store an indicator of the last uid for this folder
        // so for the next sync it syncs the actual new messages
        if (! $this->account->isInitialSyncPerformed() && $remoteFolder->isTrashOrSpam()) {
            $folder->setMeta(self::SKIPPED_LAST_UID_META_KEY, $remoteFolder->getLastUid());

            return;
        }

        if ($lastUid) {
            $this->info(sprintf('Gathering messages since last uid for folder %s.', $folder->name));

            // Delete the skipped last uid if exist
            if ($folder->hasMeta(self::SKIPPED_LAST_UID_META_KEY)) {
                $folder->removeMeta(self::SKIPPED_LAST_UID_META_KEY);
            }

            $messages = $remoteFolder->getMessagesSinceLastUid($lastUid);
        } else {
            $this->info(sprintf('Performing initial sync for folder %s.', $folder->name));

            $messages = $remoteFolder->getMessagesFrom($this->account->initial_sync_from->format('Y-m-d H:i:s'));
        }

        try {
            $this->processMessages($messages, $folder);
        } catch (UnexpectedEncodingException|UnsupportedCharsetException $e) {
            $this->error('Mail message was skipped from import because of ' . Str::of($e::class)->headline()->lower() . ' exception.');
        }

        // Sync the flags only if it's not initial sync
        if ($lastUid) {
            $this->syncFlags($folder);
        }
    }

    /**
     * Sync the account folders
     *
     * @return void
     */
    public function syncFolders()
    {
        // The uid validity must be checked first
        $totalInvalidUidValidity = $this->checkFoldersByUidValidity();

        // Check that we're not stuck in an endless uidinvalidity resync loop.
        if ($totalInvalidUidValidity > self::MAX_UIDINVALID_RESYNCS) {
            $this->error('Resynced more than MAX_UIDINVALID_RESYNCS in a row. Stopping sync.');

            $this->account->setSyncState(
                SyncState::STOPPED,
                'Detected endless uidvalidity resync loop.'
            );

            return false;
        }

        $this->removeRemotelyRemovedFolders();
    }

    /**
     * Synchronize message flags
     *
     * @param \App\Models\EmailAccountFolder $folder
     *
     * @return null
     */
    protected function syncFlags($folder)
    {
        $this->info(sprintf('Starting syncing folder %s messages flags.', $folder->name));

        $remoteFolder = $this->findFolder($folder);
        if (! $this->checkSelectable($remoteFolder)) {
            return;
        }

        // Store the total read and unread before update
        // so we can compare them later after the update so we can know
        // if sync is performed
        list($readCountBeforeUpdate, $unreadCountBeforeUpdate
        ) = [$this->getCountReadMessages($folder->id), $this->getCountUnreadMessages($folder->id)];

        // Perform the update
        $this->updateReadAndUnreadMessages($remoteFolder, $folder->id);

        // Compare previous values with current values
        if ($readCountBeforeUpdate !== $this->getCountReadMessages($folder->id) ||
                $unreadCountBeforeUpdate !== $this->getCountUnreadMessages($folder->id)) {
            $this->synced = true;
        }
    }

    /**
     * Handle removed messages
     *
     * @return void
     */
    protected function checkForRemovedMessages()
    {
        foreach ($this->account->folders->active() as $folder) {
            $remoteFolder = $this->findFolder($folder);

            if (! $this->checkSelectable($remoteFolder)) {
                continue;
            }

            // All local database stored UID's and their ID's
            $databaseUids = $this->getDatabaseMessages($folder);

            // Remote folder all UID's
            $allFolderUids = $remoteFolder->getAllUids();

            foreach ($databaseUids as $message) {
                if (! $allFolderUids->contains($message->remote_id)) {
                    $this->addMessageToDeleteQueue($message->remote_id, $folder);
                }
            }
        }
    }

    /**
     * Check folders uidvalidity
     *
     * @see  https://docs.nylas.com/docs/inconsistent-uidvalidity-value
     *
     * @return void
     */
    protected function checkFoldersByUidValidity()
    {
        $this->info('Checking folders uidvalidity.');
        $totalInvalidUidValidity = 0;

        foreach ($this->account->folders as $databaseFolder) {
            $remoteFolder = $this->findFolder($databaseFolder);

            // Perhaps the folder is deleted?
            // will catch in the removeRemotelyRemovedFolders method
            if (! $remoteFolder) {
                continue;
            }

            if ($remoteFolder->getId() != $databaseFolder->remote_id) {
                $totalInvalidUidValidity++;
                $this->info(sprintf('Found inconsistent uidvalidity for folder %s, clearing local cache', $databaseFolder->name));
                // Clear local cache, delete messages and all data
                // After that the folder will be re-created in the syncFolders method
                // On the next sync, the new messages will be fetched
                $this->deleteFolder($databaseFolder);
                $this->synced = true;
            }
        }

        return $totalInvalidUidValidity;
    }

    /**
     * Check the folder sync state
     * Useful when user injected selectable on non selectable folder via API
     *
     * @param \App\Innoclapps\MailClient\Imap\Folder $remoteFolder
     *
     * @return boolean
     */
    protected function checkSelectable($remoteFolder)
    {
        if (! $remoteFolder->isSelectable()) {
            $this->folders->markAsNotSelectable($folder->id);

            return false;
        }

        return true;
    }

    /**
     * Update the read and unread messages for a given remote folder
     * and local folder
     *
     * @param \App\Innoclapps\MailClient\Imap\Folder $remoteFolder
     * @param int $folderId
     *
     * @return void
     */
    protected function updateReadAndUnreadMessages($remoteFolder, $folderId)
    {
        $remoteFolder->getSeenIds($this->account->initial_sync_from->format('Y-m-d H:i:s'))
            ->chunk(500)
            ->each(function ($ids) use ($folderId) {
                $this->messages->markAsReadByRemoteIds($folderId, $ids->all());
            });

        $remoteFolder->getUnseenIds($this->account->initial_sync_from->format('Y-m-d H:i:s'))
            ->chunk(500)
            ->each(function ($ids) use ($folderId) {
                $this->messages->markAsUnreadByRemoteIds($folderId, $ids->all());
            });
    }

    /**
     * Get the count of total read local messages
     *
     * @param int $folderId
     *
     * @return int
     */
    protected function getCountReadMessages($folderId)
    {
        return $this->folders->countReadMessages($folderId);
    }

    /**
     * Get the count of total read local messages
     *
     * @param int $folderId
     *
     * @return int
     */
    protected function getCountUnreadMessages($folderId)
    {
        return $this->folders->countUnreadMessages($folderId);
    }
}
