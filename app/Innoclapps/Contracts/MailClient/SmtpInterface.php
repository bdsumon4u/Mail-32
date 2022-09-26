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
    /**
     * Set mail message subject
     *
     * @param  string  $subject
     * @return static
     */
    public function subject($subject);

    /**
     * Set mail message HTML body
     *
     * @param  string  $body
     * @return static
     */
    public function htmlBody($body);

    /**
     * Set mail message TEXT body
     *
     * @param  string  $body
     * @return static
     */
    public function textBody($body);

    /**
     * Set the recipients
     *
     * @param  mixed  $recipients
     * @return static
     */
    public function to($recipients);

    /**
     * Set the cc address for the mail message.
     *
     * @param  array|string  $address
     * @param  string|null  $name
     * @return static
     */
    public function cc($address, $name = null);

    /**
     * Set the replyTo address for the mail message.
     *
     * @param  array|string  $address
     * @param  string|null  $name
     * @return static
     */
    public function replyTo($address, $name = null);

    /**
     * Set the bcc address for the mail message.
     *
     * @param  array|string  $address
     * @param  string|null  $name
     * @return static
     */
    public function bcc($address, $name = null);

    /**
     * Attach a file to the message.
     *
     * @param  string  $file
     * @param  array  $options
     * @return static
     */
    public function attach($file, array $options = []);

    /**
     * Attach in-memory data as an attachment.
     *
     * @param  string  $data
     * @param  string  $name
     * @param  array  $options
     * @return static
     */
    public function attachData($data, $name, array $options = []);

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
     * Set the from header email
     *
     * @param  string  $email
     */
    public function setFromAddress($email);

    /**
     * Get the from header email
     *
     * @return string|null
     */
    public function getFromAddress();

    /**
     * Set the from header name
     *
     * @param  string  $name
     */
    public function setFromName($name);

    /**
     * Get the from header name
     *
     * @return string|null
     */
    public function getFromName();

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
