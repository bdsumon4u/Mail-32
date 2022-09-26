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

namespace App\Innoclapps\Contracts\OAuth;

interface StateStorage
{
    /**
     * Get state from storage
     *
     * @return string|null
     */
    public function get(): ?string;

    /**
     * Put state in storage
     *
     * @param  string  $value
     * @return void
     */
    public function put($value): void;

    /**
     * Check whether there is stored state
     *
     * @return bool
     */
    public function has(): bool;

    /**
     * Forget the remembered state from storage
     *
     * @return void
     */
    public function forget(): void;
}
