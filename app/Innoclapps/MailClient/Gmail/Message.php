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

namespace App\Innoclapps\MailClient\Gmail;

use App\Innoclapps\MailClient\AbstractMessage;
use App\Innoclapps\MailClient\FolderIdentifier;

class Message extends AbstractMessage
{
    /**
     * Get the message id
     *
     * @return string
     */
    public function getId()
    {
        return $this->getEntity()->getId();
    }

    /**
     * Get the internet message id
     *
     * @return string|null
     */
    public function getMessageId()
    {
        return $this->getEntity()->getInternetMessageId();
    }

    /**
     * Get the message subject
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
        return $this->getEntity()->getDate()->tz(config('app.timezone'));
    }

    /**
     * Get the Message text body
     *
     * @return string|null
     */
    public function getTextBody()
    {
        return $this->getEntity()->getPlainTextBody();
    }

    /**
     * Get the message HTML body
     *
     * @return string|null
     */
    public function getHTMLBody()
    {
        return $this->getEntity()->getHtmlBody();
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
     * Get message FROM
     *
     * @return \App\Innoclapps\Mail\Headers\AddressHeader|null
     */
    public function getFrom()
    {
        return $this->getEntity()->getFrom();
    }

    /**
     * Get message TO
     *
     * @return \App\Innoclapps\Mail\Headers\AddressHeader|null
     */
    public function getTo()
    {
        return $this->getEntity()->getTo();
    }

    /**
     * Get message CC
     *
     * @return \App\Innoclapps\Mail\Headers\AddressHeader|null
     */
    public function getCc()
    {
        return $this->getEntity()->getCc();
    }

    /**
     * Get message BCC
     *
     * @return \App\Innoclapps\Mail\Headers\AddressHeader|null
     */
    public function getBcc()
    {
        return $this->getEntity()->getBcc();
    }

    /**
     * Get message Reply-to
     *
     * @return \App\Innoclapps\Mail\Headers\AddressHeader|null
     */
    public function getReplyTo()
    {
        return $this->getEntity()->getReplyTo();
    }

    /**
     * Get message Sender
     *
     * @return \App\Innoclapps\Mail\Headers\AddressHeader|null
     */
    public function getSender()
    {
        return $this->getFrom();
    }

    /**
     * Check if the message has been read/seen
     *
     * @return bool
     */
    public function isRead()
    {
        foreach ($this->getFolders() as $identifier) {
            if ($identifier->value === 'UNREAD') {
                return false;
            }
        }

        return true;
    }

    /**
     * Check whether the message is draft
     *
     * @return bool
     */
    public function isDraft()
    {
        foreach ($this->getFolders() as $identifier) {
            if ($identifier->value === 'DRAFT') {
                return true;
            }
        }

        return false;
    }

    /**
     * Mark the message as read
     *
     * @return bool
     */
    public function markAsRead()
    {
        return $this->getEntity()->markAsRead();
    }

    /**
     * Mark the message as unread
     *
     * @return bool
     */
    public function markAsUnread()
    {
        return $this->getEntity()->markAsUnread();
    }

    /**
     * Get the message references
     *
     * @return array|null
     */
    public function getReferences()
    {
        return $this->getEntity()->getReferences();
    }

    /**
     * Get message headers
     *
     * @return \App\Innoclapps\Mail\Headers\HeadersCollection
     */
    public function getHeaders()
    {
        return $this->getEntity()->getHeaders();
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
     * Add message label
     *
     * @param  string  $label
     */
    public function addLabel($label)
    {
        return $this->getEntity()->addLabel($label);
    }

    /**
     * Initialize a reply for a message
     *
     * @return \App\Innoclapps\Google\Services\Message\MailReply
     */
    public function reply()
    {
        return $this->getEntity()->reply();
    }

    /**
     * Get the message folders remote identifiers
     *
     * @return array
     */
    public function getFolders()
    {
        return array_map(function ($label) {
            return new FolderIdentifier('id', $label);
        }, $this->getEntity()->getLabels());
    }

    /**
     * Get the message history id
     *
     * @return int|null
     */
    public function getHistoryId()
    {
        return $this->getEntity()->getHistoryId();
    }

    /**
     * Mask attachments
     *
     * @param  \Illuminate\Support\Collection  $attachments
     * @return \Illuminate\Support\Collection
     */
    protected function maskAttachments($attachments)
    {
        return $attachments->map(function ($attachment) {
            return $this->maskAttachment($attachment);
        });
    }

    /**
     * Mask attachment
     *
     * @param  array  $attachment
     * @return \App\Innoclapps\MailClient\Gmail\Attachment
     */
    protected function maskAttachment($attachment)
    {
        return new Attachment($attachment);
    }
}
