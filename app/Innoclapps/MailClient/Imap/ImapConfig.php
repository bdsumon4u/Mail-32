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

namespace App\Innoclapps\MailClient\Imap;

class ImapConfig
{
    public function __construct(
        protected string $host,
        protected int $port,
        protected ?string $encryption,
        protected string $email,
        protected bool $validateCertificate,
        protected ?string $username,
        protected string $password,
    ) {
    }

    /**
     * Get the connection server/host
     *
     * @return string
     */
    public function host(): string
    {
        return $this->host;
    }

    /**
     * Get the connection port
     *
     * @return int
     */
    public function port(): int
    {
        return $this->port;
    }

    /**
     * Get the connection encryption type
     *
     * @return string|null ssl|tls|starttls
     */
    public function encryption(): ?string
    {
        return $this->encryption ?? null;
    }

    /**
     * Get the connection email address
     *
     * @return string
     */
    public function email(): string
    {
        return $this->email;
    }

    /**
     * Whether to validate the certificate
     *
     * @return bool
     */
    public function validateCertificate(): bool
    {
        return $this->validateCertificate;
    }

    /**
     * Get connection username in case using different username
     * then the email address
     *
     * @return string|null
     */
    public function username(): ?string
    {
        return $this->username;
    }

    /**
     * Get the connection password
     *
     * @return string
     */
    public function password(): string
    {
        return $this->password;
    }
}
