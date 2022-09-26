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

use Google_Service_Gmail;

/** @property Google_Service_Gmail $service */
class Labels extends Service
{
    /**
     * Initialize new Service instance
     *
     * @param  \Google_Client  $client
     */
    public function __construct($client)
    {
        parent::__construct($client, Google_Service_Gmail::class);
    }

    /**
     * List all available user labels
     *
     * @return array
     */
    public function list()
    {
        $labels = [];
        $labelsResponse = $this->service->users_labels->listUsersLabels('me');

        if ($labelsResponse->getLabels()) {
            $labels = array_merge($labels, $labelsResponse->getLabels());
        }

        return $labels;
    }

    /**
     * Get user label by id
     *
     * @param  string  $id
     * @return \Google_Service_Gmail_Label
     */
    public function get($id)
    {
        return $this->service->users_labels->get('me', $id);
    }
}
