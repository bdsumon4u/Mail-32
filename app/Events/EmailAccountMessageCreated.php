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

namespace App\Events;

use App\Innoclapps\Contracts\MailClient\MessageInterface;
use App\Models\EmailAccountMessage;
use Illuminate\Queue\SerializesModels;

class EmailAccountMessageCreated
{
    use SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param  \App\Models\EmailAccountMessage  $message
     * @param  \App\Innoclapps\Contracts\MailClient\MessageInterface  $remoteMessage
     * @return void
     */
    public function __construct(public EmailAccountMessage $message, public MessageInterface $remoteMessage)
    {
    }
}
