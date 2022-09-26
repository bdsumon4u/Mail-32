<?php

namespace App\Enums;

enum ConnectionType: string
{
    case Gmail = 'Gmail';
    case Outlook = 'Outlook';
    case Imap = 'Imap';
}
