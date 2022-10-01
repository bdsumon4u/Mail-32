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

namespace App\Innoclapps\MailClient;

use App\Innoclapps\Contracts\MailClient\FolderInterface;
use App\Innoclapps\Contracts\MailClient\ImapInterface;
use App\Innoclapps\Contracts\MailClient\MessageInterface;
use App\Innoclapps\Contracts\MailClient\SmtpInterface;

class Client implements ImapInterface, SmtpInterface
{
    /**
     * Create new Client instance.
     *
     * @param  \App\Innoclapps\Contracts\MailClient\ImapInterface  $imap
     * @param  \App\Innoclapps\Contracts\MailClient\SmtpInterface&\App\Innoclapps\MailClient\AbstractSmtpClient  $smtp
     */
    public function __construct(protected ImapInterface $imap, protected SmtpInterface $smtp)
    {
        $this->smtp->setImapClient($imap);
    }

    /**
     * Get account folder
     *
     *
     * @param  string|int  $folder
     * @return \App\Innoclapps\Contracts\MailClient\FolderInterface
     *
     * @throws \App\Innoclapps\MailClient\Exceptions\FolderNotFoundException
     */
    public function getFolder($folder)
    {
        return $this->imap->getFolder($folder);
    }

    /**
     * Retrieve the account available folders from remote server
     *
     * @return \App\Innoclapps\MailClient\FolderCollection
     */
    public function retrieveFolders()
    {
        return $this->imap->retrieveFolders();
    }

    /**
     * Get account folders
     *
     * @return \App\Innoclapps\MailClient\FolderCollection
     */
    public function getFolders()
    {
        return $this->imap->getFolders();
    }

    /**
     * Move a given message to a given folder
     *
     * @param  \App\Innoclapps\Contracts\MailClient\MessageInterface  $message
     * @param  \App\Innoclapps\Contracts\MailClient\FolderInterface  $folder
     * @return bool
     */
    public function moveMessage(MessageInterface $message, FolderInterface $folder)
    {
        return $this->imap->moveMessage($message, $folder);
    }

    /**
     * Batch move messages to a given folder
     *
     * @param  array  $messages
     * @param  \App\Innoclapps\Contracts\MailClient\FolderInterface  $from
     * @param  \App\Innoclapps\Contracts\MailClient\FolderInterface  $to
     * @return bool|array
     */
    public function batchMoveMessages($messages, FolderInterface $from, FolderInterface $to)
    {
        return $this->imap->batchMoveMessages($messages, $from, $to);
    }

    /**
     * Permanently batch delete messages
     *
     * @param  array  $messages
     * @return void
     */
    public function batchDeleteMessages($messages)
    {
        $this->imap->batchDeleteMessages($messages);
    }

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
    public function batchMarkAsRead($messages, ?FolderIdentifier $folder = null)
    {
        return $this->imap->batchMarkAsRead($messages, $folder);
    }

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
    public function batchMarkAsUnread($messages, ?FolderIdentifier $folder = null)
    {
        return $this->imap->batchMarkAsUnread($messages, $folder);
    }

    /**
     * Get message by message identifier
     *
     *
     * @param  mixed  $id
     * @param  null|\App\Innoclapps\MailClient\FolderIdentifier  $folder
     * @return \App\Innoclapps\Contracts\MailClient\MessageInterface
     *
     * @throws \App\Innoclapps\MailClient\Exceptions\MessageNotFoundException
     */
    public function getMessage($id, ?FolderIdentifier $folder = null)
    {
        return $this->imap->getMessage($id, $folder);
    }

    public function setMessage($message)
    {
        $this->smtp->setMessage($message);

        return $this;
    }

    /**
     * Send mail message
     *
     * @return \App\Innoclapps\Contracts\MailClient\MessageInterface|null
     */
    public function send()
    {
        return $this->smtp->send();
    }

    /**
     * Reply to a given mail message
     *
     * @param  string  $remoteId
     * @param  null|\App\Innoclapps\MailClient\FolderIdentifier  $folder
     * @return \App\Innoclapps\Contracts\MailClient\MessageInterface|null
     */
    public function reply($remoteId, ?FolderIdentifier $folder = null)
    {
        return $this->smtp->reply($remoteId, $folder);
    }

    /**
     * Forward the given mail message
     *
     * @param  string  $remoteId
     * @param  null|\App\Innoclapps\MailClient\FolderIdentifier  $folder
     * @return \App\Innoclapps\Contracts\MailClient\MessageInterface|null
     */
    public function forward($remoteId, ?FolderIdentifier $folder = null)
    {
        return $this->smtp->forward($remoteId, $folder);
    }

    /**
     * Add custom headers to the message
     *
     * @param  string  $name
     * @param  string  $value
     * @return static
     */
    public function addHeader(string $name, string $value)
    {
        $this->smtp->addHeader($name, $value);

        return $this;
    }

    /**
     * Set the IMAP sent folder
     *
     * @param  \App\Innoclapps\Contracts\MailClient\FolderInterface  $folder
     * @return static
     */
    public function setSentFolder(FolderInterface $folder)
    {
        $this->imap->setSentFolder($folder);

        return $this;
    }

    /**
     * Get the sent folder
     *
     * @return \App\Innoclapps\Contracts\MailClient\FolderInterface
     */
    public function getSentFolder()
    {
        return $this->imap->getSentFolder();
    }

    /**
     * Set the IMAP trash folder
     *
     * @param  \App\Innoclapps\Contracts\MailClient\FolderInterface  $folder
     * @return static
     */
    public function setTrashFolder(FolderInterface $folder)
    {
        $this->imap->setTrashFolder($folder);

        return $this;
    }

    /**
     * Get the trash folder
     *
     * @return \App\Innoclapps\Contracts\MailClient\FolderInterface
     */
    public function getTrashFolder()
    {
        return $this->imap->getTrashFolder();
    }

    /**
     * Get the latest message from the sent folder
     *
     * @return \App\Innoclapps\Contracts\MailClient\MessageInterface|null
     *
     * @throws \App\Innoclapps\MailClient\Exceptions\ConnectionErrorException
     */
    public function getLatestSentMessage()
    {
        return $this->imap->getLatestSentMessage();
    }

    /**
     * Get the IMAP client
     *
     * @return \App\Innoclapps\Contracts\MailClient\ImapInterface
     */
    public function getImap()
    {
        return $this->imap;
    }

    /**
     * Get the SMTP client
     *
     * @return \App\Innoclapps\Contracts\MailClient\SmtpInterface
     */
    public function getSmtp()
    {
        return $this->smtp;
    }
}
