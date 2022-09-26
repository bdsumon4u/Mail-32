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

namespace App\Innoclapps\MailClient\Outlook;

use App\Innoclapps\Facades\Microsoft as Api;
use App\Innoclapps\Mail\Headers\AddressHeader;
use App\Innoclapps\Mail\Headers\HeadersCollection;
use App\Innoclapps\MailClient\AbstractMessage;
use App\Innoclapps\MailClient\Exceptions\ConnectionErrorException;
use App\Innoclapps\MailClient\FolderIdentifier;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use Microsoft\Graph\Model\BodyType;
use Microsoft\Graph\Model\FileAttachment;
use Microsoft\Graph\Model\Message as MessageModel;

class Message extends AbstractMessage
{
    /**
     * @var \App\Innoclapps\Mail\Headers\HeadersCollection|null
     */
    protected $headers;

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
     * Get the message id
     *
     * @return string|null
     */
    public function getMessageId()
    {
        // Use the prop directory from Microsoft as the headers may not be
        // always included in the response
        return str_replace(['<', '>'], '', $this->getEntity()->getInternetMessageId());
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
        // For draft messages, we will use the last modified date
        // as Microsoft use the last modified date in the drafts folder
        $props = $this->getEntity()->getProperties();

        if ($this->isDraft()) {
            $date = $this->getEntity()->getLastModifiedDateTime();
        } else {
            // Microsoft adds the received date time for the sent items too
            // to be equal as the sentDateTime, hence, only receivedDateTime
            // can be used for the message date
            $date = $this->getEntity()->getReceivedDateTime();
        }

        $tz = config('app.timezone');

        return $date ? Carbon::parse($date)->tz($tz) : Carbon::now($tz);
    }

    /**
     * Get the Message text body
     *
     * Microsoft only return one type of body
     *
     * @return string|null
     */
    public function getTextBody()
    {
        $body = $this->getEntity()->getBody();

        if ($body && $body->getContentType()->is(BodyType::TEXT)) {
            return $body->getContent();
        }
    }

    /**
     * Get the message HTML body
     *
     * @return string|null
     */
    public function getHTMLBody()
    {
        $body = $this->getEntity()->getBody();

        if ($body && $body->getContentType()->is(BodyType::HTML)) {
            return $body->getContent();
        }
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
        if (! $from = $this->getEntity()->getFrom()) {
            return null;
        }

        return new AddressHeader(
            'from',
            $from->getEmailAddress()->getAddress(),
            $from->getEmailAddress()->getName()
        );
    }

    /**
     * Get message TO
     *
     * @return \App\Innoclapps\Mail\Headers\AddressHeader|null
     */
    public function getTo()
    {
        return $this->parseAddresses('to', $this->getEntity()->getToRecipients());
    }

    /**
     * Get message CC
     *
     * @return \App\Innoclapps\Mail\Headers\AddressHeader|null
     */
    public function getCc()
    {
        return $this->parseAddresses('cc', $this->getEntity()->getCcRecipients());
    }

    /**
     * Get message BCC
     *
     * @return \App\Innoclapps\Mail\Headers\AddressHeader|null
     */
    public function getBcc()
    {
        return $this->parseAddresses('bcc', $this->getEntity()->getBccRecipients());
    }

    /**
     * Get message Reply-to
     *
     * @return \App\Innoclapps\Mail\Headers\AddressHeader|null
     */
    public function getReplyTo()
    {
        return $this->parseAddresses('reply-to', $this->getEntity()->getReplyTo());
    }

    /**
     * Get the message references
     *
     * @return array|null
     */
    public function getReferences()
    {
        /** @var ?\App\Innoclapps\Mail\Headers\IdHeader */
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

        $this->headers = new HeadersCollection;

        /**
         * @see https://github.com/microsoftgraph/microsoft-graph-docs/issues/2716
         */
        if ($headers = $this->getEntity()->getInternetMessageHeaders()) {
            foreach ($headers as $header) {
                $this->headers->pushHeader($header['name'], $header['value']);
            }
        } else {
            if ($singleValueExtendedProperties = $this->getEntity()->getSingleValueExtendedProperties()) {
                foreach ($singleValueExtendedProperties as $prop) {
                    if (! in_array($prop['id'], HeadersMap::MAP)) {
                        continue;
                    }

                    $headerName = array_flip(HeadersMap::MAP)[$prop['id']];
                    $this->headers->pushHeader($headerName, $prop['value']);
                }
            }

            // Try to set any headers via extensions
            $this->setHeadersViaExtensionsValues();
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
     * Get message Sender
     *
     * @return \App\Innoclapps\Mail\Headers\AddressHeader|null
     */
    public function getSender()
    {
        if (! $sender = $this->getEntity()->getSender()) {
            return null;
        }

        return new AddressHeader(
            'sender',
            $sender->getEmailAddress()->getAddress(),
            $sender->getEmailAddress()->getName()
        );
    }

    /**
     * Check if the message has been read/seen
     *
     * @return bool
     */
    public function isRead()
    {
        return $this->getEntity()->getIsRead() ? true : false;
    }

    /**
     * Check whether the message is draft
     *
     * @return bool
     */
    public function isDraft()
    {
        return $this->getEntity()->getIsDraft() ? true : false;
    }

    /**
     * Mark the message as read
     *
     * @return void
     *
     * @throws \App\Innoclapps\MailClient\Exceptions\ConnectionErrorException
     */
    public function markAsRead()
    {
        try {
            $message = new MessageModel;
            $message->setIsRead(true);

            Api::createPatchRequest("/me/messages/{$this->getId()}", $message)->execute();
        } catch (IdentityProviderException $e) {
            throw new ConnectionErrorException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Mark the message as unread
     *
     * @return void
     *
     * @throws \App\Innoclapps\MailClient\Exceptions\ConnectionErrorException
     */
    public function markAsUnread()
    {
        try {
            $message = new MessageModel;
            $message->setIsRead(false);

            Api::createPatchRequest("/me/messages/{$this->getId()}", $message)->execute();
        } catch (IdentityProviderException $e) {
            throw new ConnectionErrorException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Get the message folders remote identifiers
     *
     * @return array
     */
    public function getFolders()
    {
        return [new FolderIdentifier('id', $this->getEntity()->getParentFolderId())];
    }

    /**
     * Get the Outlook message conversation id
     *
     * @return string|null
     */
    public function getConversationId()
    {
        return $this->getEntity()->getConversationId();
    }

    /**
     * Check whether the message is removed
     *
     * NOTE: Applicable only fetching messages via delta
     * the removed property will exists when fetching via existing delta link
     *
     * @return bool
     */
    public function isRemoved()
    {
        return isset($this->getEntity()->getProperties()['@removed']);
    }

    /**
     * Set headers via extensions
     */
    protected function setHeadersViaExtensionsValues()
    {
        if ($extensions = $this->getEntity()->getExtensions()) {
            foreach ($extensions as $headers) {
                if ($this->isExtensionHoldsHeaders($headers)) {
                    $this->removeUnwantedExtensionProperties($headers);

                    foreach ($headers as $headerName => $headerValue) {
                        $this->headers->pushHeader($headerName, $headerValue);
                    }
                }
            }
        }
    }

    /**
     * Remove the not required extension properties
     *
     * @param  array  $extension
     * @return void
     */
    protected function removeUnwantedExtensionProperties(&$extension)
    {
        Arr::forget($extension, ['@odata.type', 'id']);
    }

    /**
     * Check whether the given extension has header
     *
     * @param  array  $extension
     * @return bool
     */
    protected function isExtensionHoldsHeaders($extension)
    {
        return str_ends_with($extension['id'], SmtpClient::OPEN_EXTENSION_HEADERS_ID);
    }

    /**
     * Mask attachments
     *
     * @param  mixed  $attachments
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
     * @return \App\Innoclapps\MailClient\Outlook\Attachment
     */
    protected function maskAttachment($attachment)
    {
        if (! $attachment instanceof FileAttachment) {
            $attachment = new FileAttachment($attachment);
        }

        return new Attachment($attachment);
    }

    /**
     * Parse Addresses
     *
     * @param  string  $type
     * @param  array|null  $addresses
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
            $all[$address['emailAddress']['address']] = $address['emailAddress']['name'];
        }

        return new AddressHeader($type, $all);
    }
}
