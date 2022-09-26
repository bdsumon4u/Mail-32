<?php

namespace App\Support\Concerns;

use App\Innoclapps\MailClient\Imap\ImapConfig;
use App\Innoclapps\MailClient\Imap\SmtpConfig;

trait HasImap
{
    /**
     * Get the Imap client configuration
     *
     * @return \App\Innoclapps\MailClient\Imap\ImapConfig
     */
    public function getImapConfig(): ImapConfig
    {
        return new ImapConfig(
            $this->imap_server,
            $this->imap_port,
            $this->imap_encryption,
            $this->email,
            $this->validate_cert,
            $this->username,
            $this->password
        );
    }

    /**
     * Get the Smtp client configuration
     *
     * @return \App\Innoclapps\MailClient\Imap\SmtpConfig
     */
    public function getSmtpConfig(): SmtpConfig
    {
        return new SmtpConfig(
            $this->smtp_server,
            $this->smtp_port,
            $this->smtp_encryption,
            $this->email,
            $this->validate_cert,
            $this->username,
            $this->password
        );
    }
}
