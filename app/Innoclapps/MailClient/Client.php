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
use Illuminate\Support\Facades\Storage;

class Client implements ImapInterface, SmtpInterface
{
    /**
     * The attachments from a storage disk.
     *
     * @var array
     */
    public array $diskAttachments = [];

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

    /**
     * Set the from header email
     *
     * @param  string  $email
     */
    public function setFromAddress($email)
    {
        $this->smtp->setFromAddress($email);

        return $this;
    }

    /**
     * Get the from header email
     *
     * @return string|null
     */
    public function getFromAddress()
    {
        return $this->smtp->getFromAddress();
    }

    /**
     * Set the from header name
     *
     * @param  string  $name
     */
    public function setFromName($name)
    {
        $this->smtp->setFromName($name);

        return $this;
    }

    /**
     * Get the from header name
     *
     * @return string|null
     */
    public function getFromName()
    {
        return $this->smtp->getFromName();
    }

    /**
     * Set mail message subject
     *
     * @param  string  $subject
     * @return static
     */
    public function subject($subject)
    {
        $this->smtp->subject($subject);

        return $this;
    }

    /**
     * Set mail message HTML body
     *
     * @param  string  $body
     * @return static
     */
    public function htmlBody($body)
    {
        $this->smtp->htmlBody($body);

        return $this;
    }

    /**
     * Set mail message TEXT body
     *
     * @param  string  $body
     * @return static
     */
    public function textBody($body)
    {
        $this->smtp->textBody($body);

        return $this;
    }

    /**
     * Set the recipients
     *
     * @param  mixed  $recipients
     * @return static
     */
    public function to($recipients)
    {
        $this->smtp->to($recipients);

        return $this;
    }

    /**
     * Set the cc address for the mail message.
     *
     * @param  array|string  $address
     * @param  string|null  $name
     * @return static
     */
    public function cc($address, $name = null)
    {
        $this->smtp->cc($address, $name);

        return $this;
    }

    /**
     * Set the bcc address for the mail message.
     *
     * @param  array|string  $address
     * @param  string|null  $name
     * @return static
     */
    public function bcc($address, $name = null)
    {
        $this->smtp->bcc($address, $name);

        return $this;
    }

    /**
     * Set the replyTo address for the mail message.
     *
     * @param  array|string  $address
     * @param  string|null  $name
     * @return static
     */
    public function replyTo($address, $name = null)
    {
        $this->smtp->replyTo($address, $name);

        return $this;
    }

    /**
     * Attach a file to the message.
     *
     * @param  string  $file
     * @param  array  $options
     * @return static
     */
    public function attach($file, array $options = [])
    {
        $this->smtp->attach($file, $options);

        return $this;
    }

    /**
     * Attach in-memory data as an attachment.
     *
     * @param  string  $data
     * @param  string  $name
     * @param  array  $options
     * @return static
     */
    public function attachData($data, $name, array $options = [])
    {
        $this->smtp->attachData($data, $name, $options);

        return $this;
    }

    /**
     * Attach a file to the message from storage.
     *
     * @param  string  $path
     * @param  string|null  $name
     * @param  array  $options
     * @return static
     */
    public function attachFromStorage($path, $name = null, array $options = [])
    {
        return $this->attachFromStorageDisk(null, $path, $name, $options);
    }

    /**
     * Attach a file to the message from storage.
     *
     * @param  string|null  $disk
     * @param  string  $path
     * @param  string|null  $name
     * @param  array  $options
     * @return static
     */
    public function attachFromStorageDisk($disk, $path, $name = null, array $options = [])
    {
        $this->diskAttachments = collect($this->diskAttachments)->push([
            'disk' => $disk,
            'path' => $path,
            'name' => $name ?? basename($path),
            'options' => $options,
        ])->unique(function ($file) {
            return $file['name'].$file['disk'].$file['path'];
        })->all();

        return $this;
    }

    /**
     * Send mail message
     *
     * @return \App\Innoclapps\Contracts\MailClient\MessageInterface|null
     */
    public function send()
    {
        // Send mail message flag
        $this->buildDiskAttachments();

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
        // Reply to mail message flag
        $this->buildDiskAttachments();

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
        // Forward mail message flag
        $this->buildDiskAttachments();

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

    /**
     * Add all of the disk attachments to the smtp client.
     *
     * @return void
     */
    protected function buildDiskAttachments()
    {
        foreach ($this->diskAttachments as $attachment) {
            $storage = Storage::disk($attachment['disk']);

            $this->attachData(
                $storage->get($attachment['path']),
                $attachment['name'] ?? basename($attachment['path']),
                array_merge(['mime' => $storage->mimeType($attachment['path'])], $attachment['options'])
            );
        }
    }
}
