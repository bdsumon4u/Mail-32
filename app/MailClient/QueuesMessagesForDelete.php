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
use App\Models\EmailAccountMessage;

trait QueuesMessagesForDelete
{
    /**
     * @var array
     */
    protected $queuedForDelete = [];

    /**
     * Get a message from the delete queue
     *
     * @param string $subject
     * @param string $messageId
     *
     * @return \App\Models\EmailAccountMessage|null
     */
    protected function getMessageFromDeleteQueue($subject, $messageId) : ?EmailAccountMessage
    {
        foreach ($this->getDeleteQueueKeys() as $folderKey) {
            $index = $this->getQueuedMessageForDeleteIndex($folderKey, $subject, $messageId);
            if (! is_null($index)) {
                return $this->queuedForDelete[$folderKey][$index];
            }
        }

        return null;
    }

    /**
     * Adds a new message to the delete queue
     *
     * @param string|int $remoteId
     * @param \App\Models\EmailAccountFolder $folder
     *
     * @return void
     */
    protected function addMessageToDeleteQueue(string|int $remoteId, EmailAccountFolder $folder) : void
    {
        $key = $this->createDeleteQueueKey($folder->id);
        $this->queuedForDelete[$key] ??= [];

        // Only messages that exists in local database are queued for delete
        if ($message = $this->findDatabaseMessageViaRemoteId($remoteId, $folder)) {
            $this->queuedForDelete[$key][] = $message;
        }
    }

    /**
     * Remove message from the delete queue
     *
     * @param string $subject
     * @param string $messageId
     *
     * @return void
     */
    protected function removeMessageFromDeleteQueue($subject, $messageId) : void
    {
        foreach ($this->getDeleteQueueKeys() as $folderKey) {
            $index = $this->getQueuedMessageForDeleteIndex($folderKey, $subject, $messageId);
            if (! is_null($index)) {
                unset($this->queuedForDelete[$folderKey][$index]);

                break;
            }
        }
    }

    /**
     * Delete all messages which are queued for delete
     *
     * @return void
     */
    protected function deleteQueuedMessages() : void
    {
        foreach ($this->queuedForDelete as $key => $messages) {
            [$string, $folderId] = explode('-', $key);

            foreach ($messages as $message) {
                $this->deleteMessage($message->remote_id, $this->folders->find($folderId));
            }
        }

        $this->queuedForDelete = [];
    }

    /**
     * Get the queued message for delete index
     *
     * @param string $queueKey
     * @param string $subject
     * @param string $messageId
     *
     * @return null|int
     */
    protected function getQueuedMessageForDeleteIndex($queueKey, $subject, $messageId) : ?int
    {
        foreach ($this->queuedForDelete[$queueKey] as $index => $message) {
            if ($message->subject == $subject && $message->message_id == $messageId) {
                return $index;
            }
        }

        return null;
    }

    /**
     * Get the queued messages keys
     *
     * @return array
     */
    protected function getDeleteQueueKeys() : array
    {
        return array_keys($this->queuedForDelete);
    }

    /**
     * Provides the queue delete key
     *
     * @param int $folderId
     *
     * @return string
     */
    protected function createDeleteQueueKey(int $folderId) : string
    {
        return 'folder-' . $folderId;
    }
}
