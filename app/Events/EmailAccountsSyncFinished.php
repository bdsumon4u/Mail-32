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

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EmailAccountsSyncFinished implements ShouldBroadcastNow
{
    use Dispatchable,
        InteractsWithSockets,
        SerializesModels;

    /**
     * Create new EmailAccountsSyncFinished instance.
     *
     * @param  bool  $syncPerformed
     */
    public function __construct(protected bool $syncPerformed)
    {
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\PrivateChannel
     */
    public function broadcastOn()
    {
        return new PrivateChannel('inbox');
    }

    /**
     * Get the data to broadcast.
     *
     * @return array
     */
    public function broadcastWith()
    {
        return [];
    }

    /**
     * Determine if this event should broadcast.
     *
     * @return bool
     */
    public function broadcastWhen()
    {
        return $this->syncPerformed;
    }
}
