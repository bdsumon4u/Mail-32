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

namespace App\Innoclapps\Contracts\MailClient;

use App\Innoclapps\MailClient\FolderIdentifier;

interface ImapInterface
{
    /**
     * Get account folder
     *
     *
     * @param  string|int  $folder Folder identifier
     * // HOTASH #return \App\Innoclapps\Contracts\MailClient\Masks\Folder
     * @return \App\Innoclapps\Contracts\MailClient\FolderInterface
     *
     * @throws \App\Innoclapps\MailClient\Exceptions\FolderNotFoundException
     */
    public function getFolder($folder);

    /**
     * Retrieve the account available folders from remote server
     *
     * @return \App\Innoclapps\MailClient\FolderCollection
     */
    public function retrieveFolders();

    /**
     * Provides the account folders
     *
     * @return \App\Innoclapps\MailClient\FolderCollection
     */
    public function getFolders();

    /**
     * Get message by message identifier
     *
     *
     * @param  mixed  $id
     * @param  null|\App\Innoclapps\MailClient\FolderIdentifier  $folder The folder identifier if necessary
     * @return \App\Innoclapps\Contracts\MailClient\MessageInterface
     *
     * @throws \App\Innoclapps\MailClient\Exceptions\MessageNotFoundException
     */
    public function getMessage($id, ?FolderIdentifier $folder = null);

    /**
     * Move a given message to a given folder
     *
     * @param  \App\Innoclapps\Contracts\MailClient\MessageInterface  $message
     * @param  \App\Innoclapps\Contracts\MailClient\FolderInterface  $folder
     * @return bool
     */
    public function moveMessage(MessageInterface $message, FolderInterface $folder);

    /**
     * Batch move messages to a given folder
     *
     * @param  array  $messages
     * @param  \App\Innoclapps\Contracts\MailClient\FolderInterface  $from
     * @param  \App\Innoclapps\Contracts\MailClient\FolderInterface  $to
     * @return bool|array
     *
     * If the method return array, it should return maps of old remote_id's with new one
     *
     * [
     *  $old => $new
     * ]
     */
    public function batchMoveMessages($messages, FolderInterface $to, FolderInterface $from);

    /**
     * Permanently batch delete messages
     *
     * @param  array  $messages
     * @return void
     */
    public function batchDeleteMessages($messages);

    /**
     * Batch mark as read messages
     *
     * @param  array  $messages
     * @param  null|\App\Innoclapps\MailClient\FolderIdentifier  $folder
     * @return bool
     *
     * @throws \App\Innoclapps\MailClient\Exceptions\ConnectionErrorException
     * @throws \App\Innoclapps\MailClient\Exceptions\FolderNotFoundException
     */
    public function batchMarkAsRead($messages, ?FolderIdentifier $folder = null);

    /**
     * Batch mark as unread messages
     *
     * @param  array  $messages
     * @param  null|\App\Innoclapps\MailClient\FolderIdentifier  $folder
     * @return bool
     *
     * @throws \App\Innoclapps\MailClient\Exceptions\ConnectionErrorException
     * @throws \App\Innoclapps\MailClient\Exceptions\FolderNotFoundException
     */
    public function batchMarkAsUnread($messages, ?FolderIdentifier $folder = null);

    /**
     * Set the IMAP sent folder
     *
     * @param  \App\Innoclapps\Contracts\MailClient\FolderInterface  $folder
     * @return static
     */
    public function setSentFolder(FolderInterface $folder);

    /**
     * Get the sent folder
     *
     * @return \App\Innoclapps\Contracts\MailClient\FolderInterface
     */
    public function getSentFolder();

    /**
     * Set the IMAP trash folder
     *
     * @param  \App\Innoclapps\Contracts\MailClient\FolderInterface  $folder
     * @return static
     */
    public function setTrashFolder(FolderInterface $folder);

    /**
     * Get the trash folder
     *
     * @return \App\Innoclapps\Contracts\MailClient\FolderInterface
     */
    public function getTrashFolder();

    /**
     * Get the latest message from the sent folder
     *
     * @return \App\Innoclapps\Contracts\MailClient\MessageInterface|null
     *
     * @throws \App\Innoclapps\MailClient\Exceptions\ConnectionErrorException
     */
    public function getLatestSentMessage();
}
