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

namespace App\Innoclapps\Google\Services;

use Google_Client;
use Google_Service;

class Service
{
    protected Google_Client $client;

    protected Google_Service $service;

    /**
     * Initialize new Service instance
     *
     * @param  \Google_Client  $client
     * @param  string|\Google_Service  $service
     * @param  mixed  $params
     */
    public function __construct($client, $service, ...$params)
    {
        $this->client = $client;
        $this->service = ! $service instanceof Google_Service ?
            new $service($this->client, ...$params) :
            $service;
    }

    /**
     * Dynamically access the service
     *
     * @param  string  $key
     * @return mixed
     */
    public function __get($key)
    {
        return $this->service->{$key};
    }
}
