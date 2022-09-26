<?php

namespace App\Innoclapps\MailClient;

use App\Enums\ConnectionType;
use App\Innoclapps\Contracts\MailClient\Connectable;
use App\Innoclapps\MailClient\Exceptions\ConnectionErrorException;
use App\Innoclapps\MailClient\Gmail\ImapClient as GmailImapClient;
use App\Innoclapps\MailClient\Gmail\SmtpClient as GmailSmtpClient;
use App\Innoclapps\MailClient\Imap\ImapClient;
use App\Innoclapps\MailClient\Imap\ImapConfig;
use App\Innoclapps\MailClient\Imap\SmtpClient;
use App\Innoclapps\MailClient\Imap\SmtpConfig;
use App\Innoclapps\MailClient\Outlook\ImapClient as OutlookImapClient;
use App\Innoclapps\MailClient\Outlook\SmtpClient as OutlookSmtpClient;
use App\Innoclapps\OAuth\AccessTokenProvider;
use Exception;

class ClientManager
{
    /**
     * Available encryption types
     */
    const ENCRYPTION_TYPES = [
        'ssl', 'tls', 'starttls',
    ];

    /**
     * Create mail client instance
     *
     * @param  \App\Enums\ConnectionType  $connectionType
     * @param  \App\Innoclapps\OAuth\AccessTokenProvider|\App\Innoclapps\MailClient\Imap\ImapConfig  $imapConfig
     * @param  \App\Innoclapps\OAuth\AccessTokenProvider|\App\Innoclapps\MailClient\Imap\SmtpConfig|null  $smtpConfig
     * @return \App\Innoclapps\MailClient\Client
     */
    public static function createClient(
        ConnectionType $connectionType,
        AccessTokenProvider|ImapConfig $imapConfig,
        AccessTokenProvider|SmtpConfig $smtpConfig = null,
    ): Client {
        $part = $connectionType === ConnectionType::Imap ? '' : $connectionType->value;

        return new Client(
            self::{'create'.$part.'ImapClient'}($imapConfig),
            // ?? $imapConfig is if is AccessTokenProvider
            self::{'create'.$part.'SmtpClient'}($smtpConfig ?? $imapConfig)
        );
    }

    /**
     * Create IMAP client instance
     *
     * @param  \App\Innoclapps\MailClient\Imap\ImapConfig  $config
     * @return \App\Innoclapps\MailClient\Imap\ImapClient
     */
    public static function createImapClient(ImapConfig $config): ImapClient
    {
        return new ImapClient($config);
    }

    /**
     * Create SMTP client instance
     *
     * @param  \App\Innoclapps\MailClient\Imap\SmtpConfig  $config
     * @return \App\Innoclapps\MailClient\Imap\SmtpClient
     */
    public static function createSmtpClient(SmtpConfig $config): SmtpClient
    {
        return new SmtpClient($config);
    }

    /**
     * Create Outlook IMAP client instance
     *
     * @param  \App\Innoclapps\OAuth\AccessTokenProvider  $token
     * @return \App\Innoclapps\MailClient\Outlook\ImapClient
     */
    public static function createOutlookImapClient(AccessTokenProvider $token): OutlookImapClient
    {
        return new OutlookImapClient($token);
    }

    /**
     * Create Outlook SMTP client instance
     *
     * @param  \App\Innoclapps\OAuth\AccessTokenProvider  $token
     * @return \App\Innoclapps\MailClient\Outlook\SmtpClient
     */
    public static function createOutlookSmtpClient(AccessTokenProvider $token): OutlookSmtpClient
    {
        return new OutlookSmtpClient($token);
    }

    /**
     * Create Gmail IMAP client instance
     *
     * @param  \App\Innoclapps\OAuth\AccessTokenProvider  $token
     * @return \App\Innoclapps\MailClient\Gmail\ImapClient
     */
    public static function createGmailImapClient(AccessTokenProvider $token): GmailImapClient
    {
        return new GmailImapClient($token);
    }

    /**
     * Create Gmail SMTP client instance
     *
     * @param  \App\Innoclapps\OAuth\AccessTokenProvider  $token
     * @return \App\Innoclapps\MailClient\Gmail\SmtpClient
     */
    public static function createGmailSmtpClient(AccessTokenProvider $token): GmailSmtpClient
    {
        return new GmailSmtpClient($token);
    }

    /**
     * Test server connection
     *
     * @param  \App\Innoclapps\Contracts\MailClient\Connectable  $client
     * @return void
     */
    public static function testConnection(Connectable $client): void
    {
        try {
            $client->testConnection();
        } catch (Exception $e) {
            throw new ConnectionErrorException($e->getMessage(), $e->getCode(), $e);
        }
    }
}
