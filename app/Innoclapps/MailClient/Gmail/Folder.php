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

use App\Innoclapps\Facades\Google as Client;
use App\Innoclapps\MailClient\AbstractFolder;
use App\Innoclapps\MailClient\Exceptions\ConnectionErrorException;
use App\Innoclapps\MailClient\Exceptions\MessageNotFoundException;
use App\Innoclapps\MailClient\MasksMessages;
use Google_Service_Exception;

class Folder extends AbstractFolder
{
    use MasksMessages;

    /**
     * Gmail Folder Delimiter
     */
    const DELIMITER = '/';

    /**
     * The next page messages identifier
     *
     * @var string
     */
    protected $next;

    /**
     * Get the folder unique identifier
     *
     * @return string
     */
    public function getId()
    {
        return $this->getEntity()->getId();
    }

    /**
     * Get folder message
     *
     * @param  string  $uid
     * @return \App\Innoclapps\MailClient\Gmail\Message
     *
     * @throws \App\Innoclapps\MailClient\Exceptions\MessageNotFoundException
     * @throws \App\Innoclapps\MailClient\Exceptions\ConnectionErrorException
     */
    public function getMessage($uid)
    {
        try {
            /** @phpstan-ignore-next-line */
            return $this->maskMessage(Client::message()
                ->withLabels($this->getId())
                ->get($uid), Message::class);
        } catch (Google_Service_Exception $e) {
            if ($e->getCode() === 404) {
                throw new MessageNotFoundException($e->getMessage(), $e->getCode(), $e);
            } elseif ($e->getCode() == 401) {
                throw new ConnectionErrorException($e->getMessage(), $e->getCode(), $e);
            }

            throw $e;
        }
    }

    /**
     * Get messages in the folder
     *
     * @param  int  $limit
     * @return \Illuminate\Support\Collection
     *
     * @throws \App\Innoclapps\MailClient\Exceptions\ConnectionErrorException
     */
    public function getMessages($limit = 50)
    {
        try {
            $messages = Client::message()
                ->withLabels($this->getId())
                ->preload()
                ->take($limit)
                ->all();

            $masked = $this->maskMessages($messages, Message::class);

            /**
             * See end of the method 'all'.
             *
             * @see \App\Innoclapps\Google\Services\Message
             * @phpstan-ignore-next-line
             */
            $this->next = $messages->next;

            return $masked;
        } catch (Google_Service_Exception $e) {
            if ($e->getCode() == 401) {
                throw new ConnectionErrorException;
            }

            throw $e;
        }
    }

    /**
     * Get messages starting from specific date and time
     *
     * @param  string  $dateTime
     * @param  int  $limit
     * @return \Illuminate\Support\Collection
     *
     * @throws \App\Innoclapps\MailClient\Exceptions\ConnectionErrorException
     */
    public function getMessagesFrom($dateTime, $limit = 50)
    {
        try {
            $messages = Client::message()
                ->withLabels($this->getId())
                ->preload()
                // ->after(strtotime($dateTime)) // HOTASH #
                ->after(strval(strtotime($dateTime)))
                ->take($limit)
                ->all();

            $masked = $this->maskMessages($messages, Message::class);

            /**
             * See end of the method 'all'.
             *
             * @see \App\Innoclapps\Google\Services\Message
             * @phpstan-ignore-next-line
             */
            $this->next = $messages->next;

            return $masked;
        } catch (Google_Service_Exception $e) {
            if ($e->getCode() == 401) {
                throw new ConnectionErrorException($e->getMessage(), $e->getCode(), $e);
            }

            throw $e;
        }
    }

    /**
     * Get the folder system name
     *
     * @return string
     */
    public function getName()
    {
        return $this->getEntity()->getName();
    }

    /**
     * Get the folder display name
     *
     * @return string
     */
    public function getDisplayName()
    {
        return last(explode(self::DELIMITER, $this->getName()));
    }

    /**
     * Check whether the folder is selectable
     *
     * @return bool
     */
    public function isSelectable()
    {
        return true;
    }

    /**
     * Check whether a message can be moved to this folder
     *
     * @return bool
     */
    public function supportMove()
    {
        return ! $this->isDraft() && ! $this->isSent();
    }

    /**
     * Fetch the next page of the messages
     *
     * @return null|\Illuminate\Support\Collection
     *
     * @throws \App\Innoclapps\MailClient\Exceptions\ConnectionErrorException
     */
    public function nextPage()
    {
        try {
            if ($result = ($this->next)()) {
                $this->next = $result->next;

                return $this->maskMessages($result, Message::class);
            }

            return null;
        } catch (Google_Service_Exception $e) {
            if ($e->getCode() == 401) {
                throw new ConnectionErrorException($e->getMessage(), $e->getCode(), $e);
            }

            throw $e;
        }
    }
}
