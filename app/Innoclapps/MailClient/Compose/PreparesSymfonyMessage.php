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

namespace App\Innoclapps\MailClient\Compose;

use App\Innoclapps\Mail\EmbeddedImagesProcessor;
use App\Innoclapps\Mail\InteractsWithSymfonyMessage;

trait PreparesSymfonyMessage
{
    use InteractsWithSymfonyMessage;

    /**
     * Prepare new symfony message with defaults
     *
     * @param  \Illuminate\Mail\Message  $message
     * @param  string  $fallbackFromAddress Pass the FROM address in case there is no from address set
     * @return \Illuminate\Mail\Message
     */
    protected function prepareSymfonyMessage($message, $fallbackFromAddress)
    {
        $fromAddress = $this->getFromAddress() ?? $fallbackFromAddress;

        if ($this->subject) {
            $message->subject($this->subject);
        }

        $message->from($fromAddress, $this->getFromName());

        $this->addSymfonyMessageContent($message);

        // When there is no reply-to headers we will just
        // set the account address as reply-to most mail servers
        // will remove this reply-to as will be the same
        // like the sender/from
        if (count($this->replyTo) === 0) {
            $message->replyTo($fromAddress, $this->getFromName());
        } else {
            foreach ($this->replyTo as $recipient) {
                $message->replyTo($recipient['address'], $recipient['name']);
            }
        }

        // Add recipients, attachments and custom headers
        $this->buildSymfonyMessageRecipients($message)
            ->buildSymfonyMessageAttachments($message)
            ->addHeadersToSymfonyMessage($message->getSymfonyMessage());

        return $message;
    }

    /**
     * Add the content to a given message.
     *
     * @param  \Illuminate\Mail\Message  $message
     * @return void
     */
    protected function addSymfonyMessageContent($message)
    {
        if ($this->htmlBody) {
            // Prepare the body and process any inline images
            $message->html((new EmbeddedImagesProcessor)(
                $this->htmlBody,
                $this->createInlineImagesProcessingFunction($message)
            ));
        }

        if ($this->textBody) {
            $message->text($this->textBody);
        }
    }

    /**
     * Set the message recipients
     *
     * @param  \Illuminate\Mail\Message  $message
     * @return static
     */
    protected function buildSymfonyMessageRecipients($message)
    {
        foreach (['cc', 'bcc', 'to'] as $type) {
            foreach ($this->{$type} as $recipient) {
                $message->{$type}($recipient['address'], $recipient['name']);
            }
        }

        return $this;
    }

    /**
     * Set the message attachments
     *
     * @param  \Illuminate\Mail\Message  $message
     * @return static
     */
    protected function buildSymfonyMessageAttachments($message)
    {
        foreach ($this->attachments as $attachment) {
            $message->attach($attachment['file'], $attachment['options']);
        }

        foreach ($this->rawAttachments as $attachment) {
            $message->attachData($attachment['data'], $attachment['name'], $attachment['options']);
        }

        return $this;
    }

    /**
     * Helper inline images processing function
     *
     * @param  \Illuminate\Mail\Message  $message
     * @return \Closure
     */
    protected function createInlineImagesProcessingFunction($message)
    {
        return function ($data, $name, $contentType) use ($message) {
            return $message->embedData($data, $name, $contentType);
        };
    }
}
