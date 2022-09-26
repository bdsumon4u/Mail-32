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

namespace App\Innoclapps\OAuth;

class AccessTokenProvider
{
    /**
     * Initialize the acess token provider class
     *
     * @param  string  $token
     * @param  string  $email
     */
    public function __construct(protected string $token, protected string $email)
    {
    }

    /**
     * Get the access token
     *
     * @return string
     */
    public function getAccessToken(): string
    {
        return $this->token;
    }

    /**
     * Get the token email adress
     *
     * @return string
     */
    public function getEmail(): string
    {
        return $this->email;
    }
}
