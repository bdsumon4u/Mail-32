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

namespace App\Innoclapps\Microsoft\Services\Batch;

class BatchPostRequest extends BatchRequest
{
    /**
     * Initialize new BatchPostRequest instance.
     *
     * @param  string  $url
     * @param  array  $body
     */
    public function __construct($url, $body = [])
    {
        parent::__construct($url, $body);
        $this->asJson();
    }

    /**
     * Get request method
     *
     * @return string
     */
    public function getMethod()
    {
        return 'POST';
    }
}
