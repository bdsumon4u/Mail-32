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

namespace App\Innoclapps\MailClient;

trait Smtpable
{
    /**
     * The sender email
     *
     * @var string|null
     */
    protected $fromEmail;

    /**
     * The sender name
     *
     * @var string|null
     */
    protected $fromName;

    /**
     * Set the from header email
     *
     * @param  string  $email
     * @return static
     */
    public function setFromAddress($email)
    {
        $this->fromEmail = $email;

        return $this;
    }

    /**
     * Get the from header email
     *
     * @return string|null
     */
    public function getFromAddress()
    {
        return $this->fromEmail;
    }

    /**
     * Set the from header name
     *
     * @param  string  $name
     * @return static
     */
    public function setFromName($name)
    {
        $this->fromName = $name;

        return $this;
    }

    /**
     * Get the from header name
     *
     * @return string|null
     */
    public function getFromName()
    {
        return $this->fromName;
    }
}
