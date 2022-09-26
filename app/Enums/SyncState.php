<?php

namespace App\Enums;

enum SyncState: string
{
    case DISABLED = 'disabled';
    case STOPPED = 'stopped';
    case ENABLED = 'enabled';
}
