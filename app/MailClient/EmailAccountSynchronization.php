<?php

namespace App\MailClient;

use App\Enums\SyncState;
use App\Innoclapps\Contracts\MailClient\FolderInterface;
use App\Innoclapps\Contracts\MailClient\ImapInterface;
use App\Innoclapps\Contracts\MailClient\MessageInterface;
use App\Innoclapps\MailClient\Exceptions\ConnectionErrorException;
use App\Innoclapps\MailClient\FolderCollection;
use App\Innoclapps\OAuth\EmptyRefreshTokenException;
use App\Models\EmailAccount;
use App\Models\EmailAccountFolder;
use App\Models\EmailAccountMessage;
use Illuminate\Support\Facades\Log;

abstract class EmailAccountSynchronization extends EmailAccountSynchronizationManager
{
    use QueuesMessagesForDelete;

    /**
     * Max time in seconds between saved DB batches
     */
    const DB_BATCH_TIME = 60;

    /**
     * Determines how often "Processed X of N emails" hint should be added to a log
     */
    const READ_HINT_COUNT = 100;

    /**
     * Determines how many emails can be stored in a database at once
     */
    const DB_BATCH_SIZE = 100;

    protected ?ImapInterface $imap;

    /**
     * @var \App\Innoclapps\MailClient\FolderCollection
     */
    protected $remoteFolders;

    protected EmailAccountMessage $messages;

    protected EmailAccountFolder $folders;

    /**
     * @var int
     */
    protected int $processStartTime;

    /**
     * Indicates the minutes the folders should be synced
     *
     * @var int
     */
    protected int $syncFoldersEvery = 60;

    /**
     * Indicates whether changed were performed
     *
     * @var bool
     */
    protected bool $synced = false;

    /**
     * Initialize new EmailAccountSynchronization instance.
     *
     * @param  \App\Models\EmailAccount  $account
     */
    public function __construct(
        protected  EmailAccount $account,
    ) {
        $this->messages = new EmailAccountMessage;
        $this->folders = new EmailAccountFolder;
    }

    /**
     * Start account messages synchronization
     *
     * @return void
     *
     * @throws \App\MailClient\Exceptions\SyncFolderTimeoutException
     */
    abstract public function syncMessages();

    /**
     * Start account folders synchronization
     *
     * @return void
     */
    abstract public function syncFolders();

    /**
     * Perform account folders and messages synchronization
     *
     * @return bool
     */
    public function perform()
    {
        $this->processStartTime = time();

        // Lazy load protection
        $this->account = $this->account->load(['folders.account', 'sentFolder.account', 'trashFolder.account']); // HOTASH #

        try {
            // Folders must by synchronized first
            if ($this->shouldSyncFolders()) {
                $this->info('Starting syncing account folders.');
                $this->syncFolders();
                $this->account->setMeta('last-folders-sync', time());
            }

            // Messages should be synced last
            $this->info('Starting syncing account messages.');
            $this->syncMessages();

            // At the end, delete queued messages (if any)
            $this->info('Deleting messages queued for deletition.');
            $this->deleteQueuedMessages();

            $this->info(sprintf(
                'Synchronization for %s performed successfully.',
                $this->account->email
            ));

            // If previously the account required auth and now it's resolved, update the database
            if ($this->account->requires_auth) {
                $this->account->setRequiresAuthentication(false);
            }

            return $this->performed();
        } catch (ConnectionErrorException $e) {
            $this->account->setRequiresAuthentication();
            Log::debug("Mail account ({$this->account->email}) connection error: {$e->getMessage()}");
            $this->error('Email account synchronization stopped because of failed authentication ['.$e->getMessage().'].');
            // To broadcast
            $this->synced = true;
        } catch (EmptyRefreshTokenException $e) {
            $this->account->setSyncState(
                SyncState::STOPPED,
                'The sync for this email account is disabled because of empty refresh token, try to remove the app from your '.explode('@', $this->account->email)[1].' account connected apps section and re-connect the account again from the Connected Accounts page.'
            );

            $this->error('Email account synchronization stopped because empty refresh token.');
        }

        return false;
    }

    /**
     * Check whether the folders should ne synchronized
     *
     * @return bool
     */
    protected function shouldSyncFolders(): bool
    {
        $lastFoldersSync = $this->account->getMeta('last-folders-sync');

        return ! $lastFoldersSync || (time() > ($lastFoldersSync + ($this->syncFoldersEvery * 60)));
    }

    /**
     * Check whether synchronization class performed any synchronization
     *
     * @return bool
     */
    public function performed(): bool
    {
        return $this->synced;
    }

    /**
     * Get the account imap client
     *
     * @return \App\Innoclapps\Contracts\MailClient\ImapInterface
     */
    public function getImapClient(): ImapInterface
    {
        if (! isset($this->imap)) {
            $this->imap = $this->account->createClient()->getImap();
        }

        return $this->imap;
    }

    /**
     * Process the messages that should be saved
     *
     * @param  \Illuminate\Support\Enumerable  $messages
     * @param  \App\Models\EmailAccountFolder|null  $folder
     *
     * Pass the folder the messages belongs to in case, unique remote_id is needed
     * e.q. for IMAP account the remote_id may not be unique, as the remote_id
     * is unique per folder
     * @return void
     */
    protected function processMessages($messages, ?EmailAccountFolder $folder = null): void
    {
        $count = 0;
        $processed = 0;
        $batch = [];
        $messages = $this->sortMessagesForSync($messages);
        $total = $messages->count();

        $this->info(sprintf('Processing messages, found %s messages.', $total));

        foreach ($messages as $message) {
            $processed++;

            if ($processed % static::READ_HINT_COUNT === 0) {
                $this->info(
                    sprintf(
                        'Processed %d of %d messages.',
                        $processed,
                        $total
                    )
                );
            }

            $count++;
            $batch[] = $message;

            if ($count === static::DB_BATCH_SIZE) {
                $this->saveMessages($batch, $folder);
                $count = 0;
                $batch = [];
            }

            if ($this->isTimeout()) {
                break;
            }
        }

        // In case of timeout break
        // insert the last processed messages
        if ($count > 0) {
            $this->saveMessages($batch, $folder);
        }

        $this->cleanUp(true, $folder);
    }

    /**
     * Save messages in database
     *
     * @param  array  $messages
     * @param  \App\Models\EmailAccountFolder|null  $folder
     * @return void
     */
    protected function saveMessages($messages, ?EmailAccountFolder $folder = null): void
    {
        foreach ($messages as $message) {
            // Check if message exists in database
            // If exists, we will perform update to this message
            if (! is_null($this->findDatabaseMessageViaRemoteId($message->getId(), $folder))) {
                $this->updateMessage($message, $message->getId(), $folder);
            } else {
                // The message is deleted from the folder
                // but maybe is moved to another folder
                // When moving messages from the application UI, this is handled
                // but when moving messages from outlook UI, we need to determine
                // if it's the same message and update in local database the new remote_id

                // NOTE: if the folder is not active, the messages from the synced and will be removed
                // from the local database
                $queuedForRemoval = $this->getMessageFromDeleteQueue($message->getSubject(), $message->getMessageId());

                // If the messages to be created exists in messages queued for removal
                // the message is moved to another folder
                // in local database, we need to keep the same message with the updates values
                if ($queuedForRemoval) {
                    // Update local values with new values
                    $this->messages->updateForAccount($message, $queuedForRemoval->id);
                    $this->info(sprintf('Updated moved message, UID: %s', $message->getId()));
                    $this->synced = true;

                    // Unset as this message won't be deleted from database
                    // because already is updated with the new ID and folders
                    $this->removeMessageFromDeleteQueue($queuedForRemoval->subject, $queuedForRemoval->message_id);
                } else {
                    $this->messages->createForAccount($this->account->id, $message);
                    $this->synced = true;
                }
            }
        }

        $this->cleanUp();
    }

    /**
     * Find database message via the message remote id
     *
     * @param  string|int  $id
     * @param  \App\Models\EmailAccountFolder|null  $folder
     * @return \App\Models\EmailAccountMessage|null
     */
    protected function findDatabaseMessageViaRemoteId(string|int $id, ?EmailAccountFolder $folder = null): ?EmailAccountMessage
    {
        return $this->getDatabaseMessages($folder)->firstWhere('remote_id', $id);
    }

    /**
     * Fetch all account database messages
     *
     * @param  \App\Models\EmailAccountFolder|null  $folder
     * @return mixed
     */
    protected function getDatabaseMessages(?EmailAccountFolder $folder = null)
    {
        $columns = ['subject', 'message_id', 'remote_id', 'id'];

        return ($folder ?
            $this->messages->getUidsByFolder($folder->id, $columns) :
            $this->messages->getUidsByAccount($this->account->id, $columns))->eager();
    }

    /**
     * Delete message
     *
     * @param  string|int  $id
     * @param  \App\Models\EmailAccountFolder|null  $folder
     * @return bool
     */
    protected function deleteMessage(string|int $id, ?EmailAccountFolder $folder = null): bool
    {
        if ($dbMessage = $this->findDatabaseMessageViaRemoteId($id, $folder)) {
            // Triggers observers
            $dbMessage->delete();

            $this->info(sprintf('Removed local message which was remotely removed, UID: %s', $id));
            $this->synced = true;

            return true;
        }

        return false;
    }

    /**
     * Update message
     *
     * @param  \App\Innoclapps\Contracts\MailClient\MessageInterface  $message
     * @param  string|int  $id
     * @param  \App\Models\EmailAccountFolder|null  $folder
     * @return bool
     */
    protected function updateMessage(MessageInterface $message, string|int $id, ?EmailAccountFolder $folder = null): bool
    {
        // Message updated, update it in our local database too
        // e.q. can be used for draft updates messages and also
        // update properties like isRead etc...
        if ($dbMessage = $this->findDatabaseMessageViaRemoteId($id, $folder)) {
            $updatedMessage = $this->messages->updateForAccount($message, $dbMessage->id);

            if ($updatedMessage->isDirty()) {
                $this->info(sprintf('Updated message, UID: %s', $id));
                $this->synced = true;

                return true;
            }
        }

        return false;
    }

    /**
     * Get the account remote folders
     *
     * @return \App\Innoclapps\MailClient\FolderCollection
     */
    protected function getFolders(): FolderCollection
    {
        if (is_null($this->remoteFolders)) {
            $this->remoteFolders = $this->getImapClient()->getFolders();
        }

        return $this->remoteFolders;
    }

    /**
     * Find remote folder by a given database folder
     *
     * @param  \App\Models\EmailAccountFolder  $folder
     * @return \App\Innoclapps\Contracts\MailClient\FolderInterface|null
     */
    protected function findFolder(EmailAccountFolder $folder): ?FolderInterface
    {
        return $this->getFolders()->find($folder->identifier());
    }

    /**
     * Find a database folder of a given remote folder
     *
     * @param  \App\Innoclapps\Contracts\MailClient\FolderInterface  $remoteFolder
     * @return \App\Models\EmailAccountFolder|null
     */
    protected function findDatabaseFolder(FolderInterface $remoteFolder): ?EmailAccountFolder
    {
        return $this->account->folders->findByIdentifier($remoteFolder->identifier());
    }

    /**
     * Remove remotely removed folders
     *
     * @return void
     */
    protected function removeRemotelyRemovedFolders(): void
    {
        $this->checkRemovedFolders(fn ($folder) => is_null($this->findFolder($folder)));
    }

    /**
     * Check remotely removed folders
     *
     * Common function for all sync managers because the same logic is used
     * on all synchronization classes, the class just needs to provide a callback
     * whether to delete the folder
     *
     * @param  \Closure  $deleteCallback Provide a callback whether to delete the folder or not
     * @return void
     */
    protected function checkRemovedFolders(\Closure $deleteCallback): void
    {
        $this->info('Starting synchronization of remotely removed folders.');

        foreach ($this->account->folders as $databaseFolder) {
            if ($deleteCallback($databaseFolder) === true) {
                $this->deleteFolder($databaseFolder);
            }
        }
    }

    /**
     * Delete folder from database
     *
     * @param  \App\Models\EmailAccountFolder  $folder
     * @return void
     */
    protected function deleteFolder(EmailAccountFolder $folder): void
    {
        $this->folders->delete($folder->id);

        // Remove the account folder once it's removed from database
        // so it doesn't interfere with the synchronization e.q. trying
        // to sync a folder which is removed
        $this->account->folders = $this->account->folders
            ->reject(fn ($dbFolder) => $dbFolder->id === $folder->id)->values();

        $this->info(sprintf(
            'Removed remotely deleted folder from local database, folder name: %s',
            $folder->name
        ));

        $this->synced = true;
    }

    /**
     * The messages must be synchronized from oldest to newest
     * as we can associate the replies associations and
     * possibility in future, create threads
     *
     * @param  \Illuminate\Support\Enumerable  $messages
     * @return \Illuminate\Support\Enumerable
     */
    protected function sortMessagesForSync($messages)
    {
        return $messages->sortBy(fn ($message) => $message->getDate())->values();
    }
}
