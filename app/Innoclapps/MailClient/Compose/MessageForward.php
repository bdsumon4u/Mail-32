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

use App\Innoclapps\MailClient\Client;
use App\Innoclapps\MailClient\FolderIdentifier;

class MessageForward extends AbstractComposer
{
    /**
     * Holds the message ID the forward is intended for
     *
     * @var string|int
     */
    protected string|int $id;

    /**
     * Holds the folder name the message belongs to
     *
     * @var \App\Innoclapps\MailClient\FolderIdentifier
     */
    protected FolderIdentifier $folder;

    /**
     * Create new MessageForward instance.
     *
     * @param  \App\Innoclapps\MailClient\Client  $client
     * @param  string|int  $remoteId
     * @param  \App\Innoclapps\MailClient\FolderIdentifier  $folder
     * @param  \App\Innoclapps\MailClient\FolderIdentifier|null  $sentFolder
     */
    public function __construct(Client $client, string|int $remoteId, FolderIdentifier $folder, ?FolderIdentifier $sentFolder = null)
    {
        parent::__construct($client, $sentFolder);

        $this->id = $remoteId;
        $this->folder = $folder;
    }

    /**
     * Forward the message
     *
     * @return \App\Innoclapps\Contracts\MailClient\MessageInterface
     */
    public function send()
    {
        return $this->client->forward($this->id, $this->folder);
    }
}
