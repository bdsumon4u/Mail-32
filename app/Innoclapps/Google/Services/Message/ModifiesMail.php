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

namespace App\Innoclapps\Google\Services\Message;

use Google_Service_Gmail_ModifyMessageRequest;

trait ModifiesMail
{
    /**
     * Marks emails as "READ". Returns string of message if fail
     *
     * @return \App\Innoclapps\Google\Services\Message\Mail
     *
     * @throws \Google_Service_Exception
     */
    public function markAsRead()
    {
        return $this->removeLabel('UNREAD');
    }

    /**
     * Marks emails as unread
     *
     * @return \App\Innoclapps\Google\Services\Message\Mail
     *
     * @throws \Google_Service_Exception
     */
    public function markAsUnread()
    {
        return $this->addLabel('UNREAD');
    }

    /**
     * @return \App\Innoclapps\Google\Services\Message\Mail
     *
     * @throws \Google_Service_Exception
     */
    public function markAsImportant()
    {
        return $this->addLabel('IMPORTANT');
    }

    /**
     * @return \App\Innoclapps\Google\Services\Message\Mail
     *
     * @throws \Google_Service_Exception
     */
    public function markAsNotImportant()
    {
        return $this->removeLabel('IMPORTANT');
    }

    /**
     * @return \App\Innoclapps\Google\Services\Message\Mail
     *
     * @throws \Google_Service_Exception
     */
    public function addStar()
    {
        return $this->addLabel('STARRED');
    }

    /**
     * @return \App\Innoclapps\Google\Services\Message\Mail
     *
     * @throws \Google_Service_Exception
     */
    public function removeStar()
    {
        return $this->removeLabel('STARRED');
    }

    /**
     * Send the email to the trash
     *
     * @return \App\Innoclapps\Google\Services\Message\Mail
     *
     * @throws \Google_Service_Exception
     */
    public function sendToTrash()
    {
        return $this->addLabel('TRASH');
    }

    /**
     * Remoe message from trash
     *
     * @return mixed
     */
    public function removeFromTrash()
    {
        return $this->removeLabel('TRASH');
    }

    /**
     * Adds labels to the email
     *
     * @param  string|array  $labels
     * @return \App\Innoclapps\Google\Services\Message\Mail
     *
     * @throws \Google_Service_Exception
     */
    public function addLabel($labels)
    {
        if (is_string($labels)) {
            $labels = [$labels];
        }

        $request = new Google_Service_Gmail_ModifyMessageRequest;
        $request->setAddLabelIds($labels);

        return $this->modify($request);
    }

    /**
     * Removes labels from the email
     *
     * @param  string|array  $labels
     * @return \App\Innoclapps\Google\Services\Message\Mail
     *
     * @throws \Google_Service_Exception
     */
    public function removeLabel($labels)
    {
        if (is_string($labels)) {
            $labels = [$labels];
        }

        $request = new Google_Service_Gmail_ModifyMessageRequest;
        $request->setRemoveLabelIds($labels);

        return $this->modify($request);
    }

    /**
     * Executes the modification
     *
     * @param  \Google_Service_Gmail_ModifyMessageRequest  $request
     * @return \App\Innoclapps\Google\Services\Message\Mail
     */
    protected function modify($request)
    {
        $message = $this->service->users_messages->modify('me', $this->getId(), $request);

        return new Mail($this->client, $message);
    }
}
