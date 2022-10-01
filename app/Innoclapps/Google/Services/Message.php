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

namespace App\Innoclapps\Google\Services;

use App\Innoclapps\Google\Services\Message\Mail;
use App\Innoclapps\Google\Services\Message\SendMail;
use Google_Service_Gmail;
use Google_Service_Gmail_BatchDeleteMessagesRequest;
use Google_Service_Gmail_BatchModifyMessagesRequest;
use Illuminate\Support\Collection;

/** @property Google_Service_Gmail $service */
class Message extends Service
{
    /**
     * Indicates whether to preload the messages and it's attachments
     *
     * @var bool
     */
    protected $preload = false;

    /**
     * Page token
     *
     * @var string|null
     */
    protected $pageToken;

    /**
     * Request params
     *
     * @var array
     */
    protected $params = [];

    /**
     * @link https://developers.google.com/gmail/api/guides/batch
     *
     * @var int
     */
    protected $batchSize = 100;

    /**
     * Message constructor.
     *
     * @param  \Google_Client  $client
     */
    public function __construct($client)
    {
        parent::__construct($client, Google_Service_Gmail::class);
    }

    /**
     * Create new SendMail instance.
     *
     * @return \App\Innoclapps\Google\Services\Message\SendMail
     */
    public function sendMail($message): SendMail
    {
        return new SendMail($this->client, $message);
    }

    /**
     * Should the messages be reloaded
     *
     * @param  bool  $state
     * @return static
     */
    public function preload($state = true)
    {
        $this->preload = $state;

        return $this;
    }

    /**
     * Returns a collection of Mail instances
     *
     * @param  null|string  $pageToken
     * @return \Illuminate\Support\Collection
     */
    public function all($pageToken = null)
    {
        if (! is_null($pageToken)) {
            $this->params['pageToken'] = $pageToken;
        }

        /**
         * List of messages. Note that each message resource contains only an id and a threadId.
         * Additional message details can be fetched using the messages.get method.
         */
        $response = $this->service->users_messages->listUsersMessages('me', $this->params);

        // Store the next page token
        $this->pageToken = $response->getNextPageToken();

        // If preload, create a batch requests for the loaded messages
        if ($this->preload) {
            /**
             * Get the messages via batch request
             *
             * @var \Illuminate\Support\Collection
             */
            $messages = $this->batchRequest($response->getMessages());
        } else {
            $messages = new Collection;
            foreach ($response->getMessages() as $message) {
                $messages->push(new Mail($this->client, $message));
            }
        }

        /**
         * If preloaded, load the attachments too
         */
        if ($this->preload) {
            $this->loadAttachments($messages);
        }

        $messages->next = function () {
            if ($this->pageToken) {
                return $this->all($this->pageToken);
            }

            return false;
        };

        return $messages;
    }

    /**
     * Get message by id
     *
     * @param  string  $id
     * @return \App\Innoclapps\Google\Services\Message\Mail
     */
    public function get($id): Mail
    {
        /**
         * @var \Google_Service_Gmail_Message
         */
        $message = $this->service->users_messages->get('me', $id);

        return new Mail($this->client, $message);
    }

    /**
     * Creates a batch request to get all emails in a single call
     *
     * @param  array|\Illuminate\Support\Collection  $messages
     * @return \Illuminate\Support\Collection
     */
    public function batchRequest($messages)
    {
        $this->client->setUseBatch(true);

        $batchMessages = new Collection;

        if (! $messages instanceof Collection) {
            $messages = new Collection($messages);
        }

        $messages->chunk($this->batchSize)
            ->each(function ($messages) use (&$batchMessages) {
                $batch = $this->service->createBatch();

                foreach ($messages as $key => $message) {
                    /**
                     * @var \GuzzleHttp\Psr7\Request
                     */
                    $request = $this->service->users_messages->get('me', $message->getId());

                    $batch->add($request, $key + 1);
                }

                $batchResponse = $batch->execute();

                foreach ($batchResponse as $message) {
                    if (! $message instanceof \Google_Service_Exception) {
                        $batchMessages->push(new Mail($this->client, $message));
                    }
                }
            });

        $this->client->setUseBatch(false);

        return $batchMessages;
    }

    /**
     * Batch move messages to a given folder
     *
     * @param  array  $messages
     * @param  array  $removeLabelIds
     * @param  array  $addLabelIds
     * @return bool
     */
    public function batchModify($messages, $removeLabelIds = [], $addLabelIds = [])
    {
        $request = new Google_Service_Gmail_BatchModifyMessagesRequest();

        $request->setRemoveLabelIds($removeLabelIds);
        $request->setAddLabelIds($addLabelIds);
        $request->setIds($messages);

        $this->service->users_messages->batchModify('me', $request);

        return true;
    }

    /**
     * Batch delete messages
     *
     * @param  array  $messages
     * @return void
     */
    public function batchDelete($messages)
    {
        $request = new Google_Service_Gmail_BatchDeleteMessagesRequest();

        $request->setIds($messages);

        $this->service->users_messages->batchDelete('me', $request);
    }

    /**
     * Returns boolean if the page token variable is null or not
     *
     * @return bool
     */
    public function hasNextPage()
    {
        return (bool) $this->pageToken;
    }

    /**
     * Limit the messages coming from the query
     *
     * @param  int  $number
     * @return static
     */
    public function take($number)
    {
        $this->params['maxResults'] = abs((int) $number);

        return $this;
    }

    /**
     * Whether to include the spam and the trash messages
     *
     * @param  bool  $boolean
     * @return static
     */
    public function includeSpamTrash($boolean = true)
    {
        $this->params['includeSpamTrash'] = $boolean;

        return $this;
    }

    /**
     * Filter messages only with specific label ids
     *
     * @param  string|array  $ids
     * @return static
     */
    public function withLabels($ids)
    {
        $this->params['labelIds'] = (array) $ids;

        return $this;
    }

    /**
     * Filter messages by subject
     *
     * @param  string  $query
     * @return static
     */
    public function subject(string $query)
    {
        $this->addQuery("[{$query}]");

        return $this;
    }

    /**
     * Filter to get only emails to a specific email address
     *
     * @param  string  $email
     * @return static
     */
    public function to(string $email)
    {
        $this->addQuery("to:{$email}");

        return $this;
    }

    /**
     * Filter to get only emails from a specific email address
     *
     * @param $email
     * @return static
     */
    public function from(string $email)
    {
        $this->addQuery("from:{$email}");

        return $this;
    }

    /**
     * Filter to get only emails after a specific date
     *
     * @param $date
     * @return static
     */
    public function after(string $date)
    {
        $this->addQuery("after:{$date}");

        return $this;
    }

    /**
     * Filter to get only emails before a specific date
     *
     * @param $date
     * @return static
     */
    public function before(string $date)
    {
        $this->addQuery("before:{$date}");

        return $this;
    }

    /**
     * Filters emails by label
     *
     * Example:
     * starred, inbox, spam, chats, sent, draft, trash
     *
     * @param  string  $box
     * @return static
     */
    public function in(string $box)
    {
        $this->addQuery("in:{$box}");

        return $this;
    }

    /**
     * Filter emails with attachment
     *
     * @return static
     */
    public function hasAttachment()
    {
        $this->addQuery('has:attachment');

        return $this;
    }

    /**
     * Eager load the messages attachments
     *
     * @param  \Illuminate\Support\Collection  $messages
     * @return static
     */
    public function loadAttachments(&$messages)
    {
        $attachments = $this->prepareAttachmentsForBatch($messages);

        $this->client->setUseBatch(true);

        $attachments->chunk($this->batchSize)
            ->each(function ($attachments) use (&$messages) {
                $batch = $this->service->createBatch();

                foreach ($attachments as $data) {
                    $batch->add(
                        $this->service->users_messages_attachments->get('me', $data->message_id, $data->attachment_id),
                        $data->attachment_key.'-'.$data->message_key
                    );
                }

                $this->handleAttachmentsBatchResponse($batch->execute(), $messages);
            });

        $this->client->setUseBatch(false);

        return $this;
    }

    /**
     * Prepare the attachments data for batch request
     *
     * @param  \Illuminate\Support\Collection  $messages
     * @return \Illuminate\Support\Collection
     */
    protected function prepareAttachmentsForBatch($messages)
    {
        $data = new Collection;

        foreach ($messages as $messageKey => $message) {
            foreach ($message->getAttachments() as $attachmentKey => $attachment) {
                $data->push((object) [
                    'attachment_id' => $attachment->getId(),
                    'message_id' => $message->getId(),
                    'message_key' => $messageKey,
                    'attachment_key' => $attachmentKey,
                ]);
            }
        }

        return $data;
    }

    /**
     * Handles the attachments batch response
     *
     * @param  array  $response
     * @param  \Illuminate\Support\Collection  $messages
     * @return void
     */
    protected function handleAttachmentsBatchResponse($response, &$messages)
    {
        foreach ($response as $key => $attachment) {
            // e.q. response-0-2
            $keyPieces = explode('-', $key);

            $attachmentKey = $keyPieces[1];
            $messageKey = $keyPieces[2];

            $messages->get($messageKey)
                ->getAttachments()
                ->get($attachmentKey)
                ->setContent($attachment->getData());
        }
    }

    /**
     * Add query to params
     *
     * @param  string  $query
     */
    protected function addQuery($query)
    {
        if (isset($this->params['q'])) {
            $this->params['q'] .= ' '.$query;
        } else {
            $this->params['q'] = $query;
        }

        return $this;
    }
}
