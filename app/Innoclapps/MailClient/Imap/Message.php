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

namespace App\Innoclapps\MailClient\Imap;

use App\Innoclapps\Mail\Headers\AddressHeader;
use App\Innoclapps\Mail\Headers\HeadersCollection;
use App\Innoclapps\MailClient\AbstractMessage;
use App\Innoclapps\MailClient\FolderIdentifier;
use Illuminate\Support\Carbon;
use ZBateson\MailMimeParser\MailMimeParser;

class Message extends AbstractMessage
{
    /**
     * The message headers
     *
     * @var \App\Innoclapps\Mail\Headers\HeadersCollection|null
     */
    protected $headers;

    /**
     * The message folder
     *
     * @var \App\Innoclapps\Contracts\MailClient\FolderInterface
     */
    protected $folder;

    /**
     * Get the message uuid
     *
     * @return int
     */
    public function getId()
    {
        return $this->getEntity()->getNumber();
    }

    /**
     * Get the internet message id
     *
     * @return string|null
     */
    public function getMessageId()
    {
        $messageIdHeader = $this->getHeaders()->find('message-id');

        if ($messageIdHeader) {
            return $messageIdHeader->getValue();
        }
    }

    /**
     * Get the message id
     *
     * @return string|null
     */
    public function getSubject()
    {
        return $this->getEntity()->getSubject();
    }

    /**
     * Get the message date
     *
     * @return \Illuminate\Support\Carbon
     */
    public function getDate()
    {
        $date = $this->getEntity()->getDate();
        $tz = config('app.timezone');

        return $date ? Carbon::parse($date)->tz($tz) : Carbon::now($tz);
    }

    /**
     * Get the Message text body
     *
     * @return string|null
     */
    public function getTextBody()
    {
        return $this->getEntity()->getBodyText();
    }

    /**
     * Get the message HTML body
     *
     * @return string|null
     */
    public function getHTMLBody()
    {
        return $this->getEntity()->getBodyHtml();
    }

    /**
     * Get the messsage attachments
     *
     * @return \Illuminate\Support\Collection
     */
    public function getAttachments()
    {
        return $this->maskAttachments($this->getEntity()->getAttachments());
    }

    /**
     * Get message from
     *
     * @return \App\Innoclapps\Mail\Headers\AddressHeader|null
     */
    public function getFrom()
    {
        $from = $this->getEntity()->getFrom();

        if (! $from) {
            return null;
        }

        return new AddressHeader('from', $from->getAddress(), $from->getName());
    }

    /**
     * Get message to
     *
     * @return \App\Innoclapps\Mail\Headers\AddressHeader|null
     */
    public function getTo()
    {
        return $this->parseAddresses('to', $this->getEntity()->getTo());
    }

    /**
     * Get message CC
     *
     * @return \App\Innoclapps\Mail\Headers\AddressHeader|null
     */
    public function getCc()
    {
        return $this->parseAddresses('cc', $this->getEntity()->getCc());
    }

    /**
     * Get message bcc
     *
     * @return \App\Innoclapps\Mail\Headers\AddressHeader|null
     */
    public function getBcc()
    {
        return $this->parseAddresses('bcc', $this->getEntity()->getBcc());
    }

    /**
     * Get message reply to
     *
     * @return \App\Innoclapps\Mail\Headers\AddressHeader|null
     */
    public function getReplyTo()
    {
        return $this->parseAddresses('reply-to', $this->getEntity()->getReplyTo());
    }

    /**
     * Get message sender
     *
     * @return \App\Innoclapps\Mail\Headers\AddressHeader|null
     */
    public function getSender()
    {
        $sender = $this->getEntity()->getSender();

        // The ddeboer library thinks that multiple senders
        // may exists
        if (count($sender) > 0) {
            return new AddressHeader('sender', $sender[0]->getAddress(), $sender[0]->getName());
        }

        return null;
    }

    /**
     * Check if the message has been read/seen
     *
     * @return bool
     */
    public function isRead()
    {
        return $this->getEntity()->isSeen();
    }

    /**
     * Check whether the message is draft
     *
     * @return bool
     */
    public function isDraft()
    {
        return stripos($this->folder->getName(), 'draft') !== false;
    }

    /**
     * Mark the message as read
     *
     * @return bool
     */
    public function markAsRead()
    {
        return $this->getEntity()->markAsSeen();
    }

    /**
     * Mark the message as unread
     *
     * @return bool
     */
    public function markAsUnread()
    {
        return $this->getEntity()->clearFlag('\Seen');
    }

    /**
     * Get the message references
     *
     * @return array|null
     */
    public function getReferences()
    {
        /** @var ?\App\Innoclapps\Mail\Headers\IdHeader $header */
        $header = $this->getHeader('References');

        return $header ? $header->getIds() : null; // HOTASH #
    }

    /**
     * Get message headers
     *
     * @return \App\Innoclapps\Mail\Headers\HeadersCollection
     */
    public function getHeaders()
    {
        if (! is_null($this->headers)) {
            return $this->headers;
        }

        $mailParser = new MailMimeParser;

        $message = $mailParser->parse($this->getEntity()->getRawHeaders(), true);

        $this->headers = new HeadersCollection;

        foreach ($message->getAllHeaders() as $header) {
            $this->headers->pushHeader($header->getName(), $header->getRawValue());
        }

        return $this->headers;
    }

    /**
     * Get message header
     *
     * @param  string  $name
     * @return \App\Innoclapps\Mail\Headers\Header|\App\Innoclapps\Mail\Headers\AddressHeader|\App\Innoclapps\Mail\Headers\IdHeader|\App\Innoclapps\Mail\Headers\DateHeader|null
     */
    public function getHeader($name)
    {
        return $this->getHeaders()->find($name);
    }

    /**
     * Get the message folders remote identifiers
     *
     * @return array
     */
    public function getFolders()
    {
        return [new FolderIdentifier('name', $this->folder->getName())];
    }

    /**
     * Set the message folder
     *
     * @param  \App\Innoclapps\Contracts\MailClient\FolderInterface  $folder
     * @return static
     */
    public function setFolder($folder)
    {
        $this->folder = $folder;

        return $this;
    }

    /**
     * Mask attachments
     *
     * @param  array  $attachments
     * @return \Illuminate\Support\Collection
     */
    protected function maskAttachments($attachments)
    {
        if (! $attachments) {
            $attachments = [];
        }

        return collect($attachments)->map(function ($attachment) {
            return $this->maskAttachment($attachment);
        })->values();
    }

    /**
     * Mask attachment
     *
     * @param  mixed  $attachment
     * @return \App\Innoclapps\MailClient\Imap\Attachment
     */
    protected function maskAttachment($attachment)
    {
        return new Attachment($attachment);
    }

    /**
     * Parse Addresses
     *
     * @param  string  $type
     * @param  array  $addresses
     * @return \App\Innoclapps\Mail\Headers\AddressHeader|null
     */
    protected function parseAddresses($type, $addresses)
    {
        /** @phpstan-ignore-next-line */
        if (! $addresses || is_array($addresses) && count($addresses) === 0) {
            return null;
        }

        $all = [];

        foreach ($addresses as $address) {
            $all[$address->getAddress()] = $address->getName();
        }

        return new AddressHeader($type, $all);
    }
}
