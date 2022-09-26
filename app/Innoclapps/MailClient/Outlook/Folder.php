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
use App\Innoclapps\MailClient\AbstractFolder;
use App\Innoclapps\MailClient\Exceptions\ConnectionErrorException;
use App\Innoclapps\MailClient\Exceptions\MessageNotFoundException;
use App\Innoclapps\MailClient\FolderType;
use App\Innoclapps\MailClient\MasksMessages;
use GuzzleHttp\Exception\ClientException;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use Microsoft\Graph\Model\MailFolder;
use Microsoft\Graph\Model\Message as MessageModel;

class Folder extends AbstractFolder
{
    use MasksFolders,
        MasksMessages,
        ProvidesMessageUri;

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
     * @return \App\Innoclapps\MailClient\Outlook\Message
     *
     * @throws \App\Innoclapps\MailClient\Exceptions\MessageNotFoundException
     * @throws \App\Innoclapps\MailClient\Exceptions\ConnectionErrorException
     */
    public function getMessage($uid)
    {
        try {
            /** @phpstan-ignore-next-line */
            return $this->maskMessage(Api::createGetRequest($this->getMessageUri($uid))
                ->setReturnType(MessageModel::class)
                ->execute(), Message::class);
        } catch (ClientException $e) {
            if ($e->getCode() === 404) {
                throw new MessageNotFoundException;
            }

            throw $e;
        } catch (IdentityProviderException $e) {
            throw new ConnectionErrorException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Get messages
     *
     * @return \Illuminate\Support\Collection
     *
     * @throws \App\Innoclapps\MailClient\Exceptions\ConnectionErrorException
     */
    public function getMessages()
    {
        try {
            $iterator = Api::createCollectionGetRequest($this->getFolderMessagesUri($this->getId()))
                ->setReturnType(MessageModel::class);

            return $this->maskMessages(Api::iterateCollectionRequest($iterator), Message::class);
        } catch (IdentityProviderException $e) {
            throw new ConnectionErrorException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Get messages starting from specific date and time
     *
     * @param  string  $dateTime
     * @return \Illuminate\Support\Collection
     *
     * @throws \App\Innoclapps\MailClient\Exceptions\ConnectionErrorException
     */
    public function getMessagesFrom($dateTime)
    {
        return $this->getDeltaMessages(null, $dateTime);
    }

    /**
     * https://docs.microsoft.com/en-us/graph/delta-query-messages
     *
     * @param  null|string  $deltaLink
     * @param  null|string  $startFrom Get messages starting from specific date and time
     * @return \Illuminate\Support\Collection
     *
     * @throws \App\Innoclapps\MailClient\Exceptions\ConnectionErrorException
     */
    public function getDeltaMessages($deltaLink = null, $startFrom = null)
    {
        $endpoint = $deltaLink ?? $this->getMessagesDeltaUri($this->getId());

        if (! $deltaLink && $startFrom) {
            /**
             * @link https://docs.microsoft.com/en-us/graph/query-parameters#filter-parameter
             *
             * The only supported $filter expresssions are $filter=receivedDateTime+ge+{value}
             * or $filter=receivedDateTime+gt+{value}.
             * @link https://docs.microsoft.com/en-us/graph/api/message-delta?view=graph-rest-1.0&tabs=http#odata-query-parameters
             */
            $startFrom = (new \DateTime($startFrom))->format('Y-m-d\TH:i:s\Z');
            // $endpoint .= '&' . urlencode("\$filter=receivedDateTime ge '$startFrom'");
            $endpoint .= '&$filter=receivedDateTime ge '.$startFrom;
        }

        try {
            $deltaIterator = Api::createCollectionGetRequest($endpoint)->setReturnType(MessageModel::class);

            $messages = $this->maskMessages(
                Api::iterateCollectionRequest($deltaIterator),
                Message::class
            );
        } catch (IdentityProviderException $e) {
            throw new ConnectionErrorException($e->getMessage(), $e->getCode(), $e);
        }

        /**
         * // HOTASH # Error
         *
         * @phpstan-ignore-next-line
         */
        $messages->deltaLink = $deltaIterator->getDeltaLink();

        return $messages;
    }

    /**
     * Get the folder system name
     *
     * @return string
     */
    public function getName()
    {
        return $this->getDisplayName();
    }

    /**
     * Get the folder display name
     *
     * @return string
     */
    public function getDisplayName()
    {
        return $this->getEntity()->getDisplayName();
    }

    /**
     * Get the folder well known name property
     *
     * @return string
     *
     * @throws \App\Innoclapps\MailClient\Exceptions\ConnectionErrorException
     */
    public function getWellKnownName()
    {
        return $this->getEntity()->getProperties()['wellKnownName'] ?? null;
    }

    /**
     * Check whether the folder has child folders
     *
     * @return bool
     */
    public function hasChildren()
    {
        return $this->getEntity()->getChildFolderCount() > 0;
    }

    /**
     * Get folder children.
     *
     * @return \App\Innoclapps\MailClient\FolderCollection|array
     *
     * @throws \App\Innoclapps\MailClient\Exceptions\ConnectionErrorException
     */
    public function getChildren()
    {
        if ($this->children) {
            return $this->children;
        }

        if ($this->hasChildren()) {
            // The folders relations are not deep loaded, in this case
            // the folder has children but they are not loaded,
            // we need to fetch the children for the folder separately
            // Also when initially fetching the folders
            // The childrens are array, we need to mask them
            if ($children = $this->getEntity()->getChildFolders()) {
                $children = array_map(function ($child) {
                    return new MailFolder($child);
                }, $children);
            } else {
                $children = $this->fetchFolderChildren();
            }

            return $this->children = $this->maskFolders($children);
        }

        // No children, set the property as array to prevent fetching the next
        // time when this method is accessed
        return $this->children = [];
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
     * Get the folder type
     *
     * @return string|null
     */
    public function getType()
    {
        $type = parent::getType();
        $wellKnownName = $this->getWellKnownName();

        $map = FolderType::KNOWN_FOLDER_NAME_MAP;

        if (! $type && array_key_exists($wellKnownName, $map)) {
            return $map[$wellKnownName];
        }

        // null
        return $type;
    }

    /**
     * Fetch the folder children
     *
     * @return array
     *
     * @throws \App\Innoclapps\MailClient\Exceptions\ConnectionErrorException
     */
    protected function fetchFolderChildren()
    {
        try {
            $iterator = Api::createCollectionGetRequest("/me/mailFolders/{$this->getId()}/childFolders")
                ->setReturnType(MailFolder::class);

            return Api::iterateCollectionRequest($iterator);
        } catch (IdentityProviderException $e) {
            throw new ConnectionErrorException($e->getMessage(), $e->getCode(), $e);
        }
    }
}
