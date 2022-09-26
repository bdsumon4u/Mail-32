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

namespace App\Innoclapps\OAuth\State\StorageDrivers;

use App\Innoclapps\Contracts\OAuth\StateStorage;
use Illuminate\Support\Facades\Session as Storage;

class Session implements StateStorage
{
    /**
     * The state session key
     *
     * @var string
     */
    protected $key = 'oauth2state';

    /**
     * Get state from storage
     *
     * @return string|null
     */
    public function get(): ?string
    {
        return Storage::get($this->key);
    }

    /**
     * Put state in storage
     *
     * @param  string  $value
     * @return void
     */
    public function put($value): void
    {
        Storage::put($this->key, $value);
    }

    /**
     * Check whether there is stored state
     *
     * @return bool
     */
    public function has(): bool
    {
        return Storage::has($this->key);
    }

    /**
     * Forget the remembered state from storage
     *
     * @return void
     */
    public function forget(): void
    {
        Storage::forget($this->key);
    }
}
