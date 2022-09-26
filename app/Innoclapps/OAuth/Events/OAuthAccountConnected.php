<?php

namespace App\Innoclapps\OAuth\Events;

use App\Models\OAuthAccount;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OAuthAccountConnected
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create new instance of OAuthAccountConnected
     *
     * @param  \App\Models\OAuthAccount  $account
     */
    public function __construct(public OAuthAccount $account)
    {
    }
}
