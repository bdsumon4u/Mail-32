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

namespace App\Innoclapps\Google\Services\Message;

use Google_Client;

class MailReply extends Compose
{
    /**
     * Initialize new MailReply instance
     *
     * @param  \Google_Client  $client
     * @param  \App\Innoclapps\Google\Services\Message\Mail  $reply
     */
    public function __construct(Google_Client $client, protected Mail $reply)
    {
        parent::__construct($client);
    }

    /**
     * Creates the raw message content
     *
     * We will override the method in order to add
     * additional headers for the reply message just before
     * creating the raw content for the send method
     *
     * @return string
     */
    protected function createRawMessage()
    {
        $references = $this->reply->getReferences();
        $references[] = $this->reply->getInternetMessageId();

        $this->addSymfonyMessageInReplyToHeader($this->message, $this->reply->getInternetMessageId())
            ->addSymfonyMessageReferencesHeader($this->message, $references);

        return parent::createRawMessage();
    }

    /**
     * Get the message service
     *
     * @return \Google_Service_Gmail_Message
     */
    protected function getMessageService()
    {
        $service = parent::getMessageService();

        $service->setThreadId($this->reply->getThreadId());

        return $service;
    }
}
