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

namespace App\Innoclapps\Contracts\MailClient;

use App\Innoclapps\MailClient\FolderIdentifier;

interface SmtpInterface
{
    public function setMessage($message);

    /**
     * Send mail message
     *
     * @return \App\Innoclapps\Contracts\MailClient\MessageInterface|null
     *
     * The method should return null if the email provider uses queue for sending the
     * emails, in this case, if the method return null, this means that the message
     * is queued for sending and we don't have an option to fetch the message immediately
     * after sending, we need to wait for application synchronization
     */
    public function send();

    /**
     * Add custom headers to the message
     *
     * @param  string  $name
     * @param  string  $value
     * @return static
     */
    public function addHeader(string $name, string $value);

    /**
     * Reply to a given mail message
     *
     * @param  string  $remoteId
     * @param  null|\App\Innoclapps\MailClient\FolderIdentifier  $folder
     * @return \App\Innoclapps\Contracts\MailClient\MessageInterface|null
     *
     * The method should return null if the email provider uses queue for sending the
     * emails, in this case, if the method return null, this means that the message
     * is queued for sending and we don't have an option to fetch the message immediately
     * after sending, we need to wait for application synchronization
     */
    public function reply($remoteId, ?FolderIdentifier $folder = null);

    /**
     * Forward the given mail message
     *
     * @param  string  $remoteId
     * @param  null|\App\Innoclapps\MailClient\FolderIdentifier  $folder
     * @return \App\Innoclapps\Contracts\MailClient\MessageInterface|null
     *
     * The method should return null if the email provider uses queue for sending the
     * emails, in this case, if the method return null, this means that the message
     * is queued for sending and we don't have an option to fetch the message immediately
     * after sending, we need to wait for application synchronization
     */
    public function forward($remoteId, ?FolderIdentifier $folder = null);
}
