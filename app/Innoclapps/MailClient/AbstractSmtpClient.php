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

use App\Innoclapps\Contracts\MailClient\SmtpInterface;
use Egulias\EmailValidator\EmailValidator;
use Egulias\EmailValidator\Validation\RFCValidation;
use Illuminate\Contracts\Support\Arrayable;
use Pelago\Emogrifier\CssInliner;
use Traversable;

abstract class AbstractSmtpClient implements SmtpInterface
{
    protected $message;

    /**
     * The SMTP client may need to the IMAP client e.q. to fetch a message(s)
     *
     * @var \App\Innoclapps\Contracts\MailClient\ImapInterface|\App\Innoclapps\MailClient\AbstractImapClient
     */
    protected $imap;

    /**
     * The message custom header
     *
     * @var array
     */
    protected $headers = [
        [
            'name' => 'X-Hotash-App',
            'value' => 'true',
        ],
    ];

    public function setMessage($message)
    {
        $this->message = $message;

        return $this;
    }

    /**
     * Add email message custom headers
     *
     * @param  string  $name
     * @param  string  $value
     * @return static
     */
    public function addHeader(string $name, string $value)
    {
        $this->headers[] = [
            'name' => $name,
            'value' => $value,
        ];

        return $this;
    }

    /**
     * Sets the imap client related to this SMTP client
     *
     * @param  \App\Innoclapps\Contracts\MailClient\ImapInterface  $client
     * @return static
     */
    public function setImapClient($client)
    {
        $this->imap = $client;

        return $this;
    }

    /**
     * Create reply subject with system special reply prefix
     *
     * @param  string  $subject
     * @return string
     */
    public function createReplySubject($subject)
    {
        return config('innoclapps.mail_client.reply_prefix').trim(
            preg_replace($this->cleanupSubjectSearch(), '', $subject)
        );
    }

    /**
     * Create forward subject with system special forward prefix
     *
     * @param  string  $subject
     * @return string
     */
    public function createForwardSubject($subject)
    {
        return config('innoclapps.mail_client.forward_prefix').trim(
            preg_replace($this->cleanupSubjectSearch(), '', $subject)
        );
    }

    /**
     * Get the clean up subject search regex
     *
     * @link https://en.wikipedia.org/wiki/List_of_email_subject_abbreviations
     *
     * @return array
     */
    protected function cleanupSubjectSearch()
    {
        return [
            // Re
            '/RE\:/i', '/SV\:/i', '/Antw\:/i', '/VS\:/i', '/RE\:/i',
            '/REF\:/i', '/ΑΠ\:/i', '/ΣΧΕΤ\:/i', '/Vá\:/i', '/R\:/i',
            '/RIF\:/i', '/BLS\:/i', '/RES\:/i', '/Odp\:/i', '/YNT\:/i',
            '/ATB\:/i',
            // FW
            '/FW\:/i', '/FWD\:/i',
            '/Doorst\:/i', '/VL\:/i', '/TR\:/i', '/WG\:/i', '/ΠΡΘ\:/i',
            '/Továbbítás\:/i', '/I\:/i', '/FS\:/i', '/TRS\:/i', '/VB\:/i',
            '/RV\:/i', '/ENC\:/i', '/PD\:/i', '/İLT\:/i', '/YML\:/i',
        ];
    }

    /**
     * Create inline version of the given message
     *
     * @param  \App\Innoclapps\Contracts\MailClient\MessageInterface  $message Previous message
     * @param  \Closure  $callback
     * @return string
     */
    protected function inlineMessage($message, $callback)
    {
        // Let's try to include the messages inline attachments
        // If the message is composed with text only, the html body may be empty
        // We won't need any replacements, will use just the text body
        $body = $message->getHtmlBody() ?
            // The callback should return either the new contentid of the inline attachment or return the data in base64
            // e.q. "data:image/jpeg;base64,...."  or any custom logic e.q. /media file path when storing the attachment
            $message->getPreviewBody($callback) :
            $message->getTextBody();

        // Maybe the message was empty?
        if (empty($body)) {
            return $body;
        }

        return 'Inline Message';
        // HOTASH #
        // return CssInliner::fromHtml($body)
        //     ->inlineCss()
        //     ->renderBodyContent();
        // HOTASH #
    }

    /**
     * Create reply body with quoted message
     *
     * @param  \App\Innoclapps\Contracts\MailClient\MessageInterface  $message Previous message
     * @param  \Closure  $callback
     * @return string|null
     */
    public function createQuoteOfPreviousMessage($message, $callback)
    {
        $date = $message->getDate();
        $from = htmlentities('<').$message->getFrom()->getAddress().htmlentities('>');

        if ($name = $message->getFrom()->getPersonName()) {
            $from = $name.' '.$from;
        }

        $wroteText = 'On '.$date->format('D, M j, Y').', at '.$date->format('g:i A').' '.$from.' wrote:';
        $quote = $this->inlineMessage($message, $callback);

        // Maybe the message was empty?
        if (empty($quote)) {
            return $quote;
        }

        // 2 new lines allow the EmailReplyParser to properly determine the actual reply message
        return "\n\n".$wroteText."\n"."<blockquote class=\"concord_quote\">$quote</blockquote>";
    }

    /**
     * Add address
     *
     * @param  string|array  $address
     * @param  string  $name
     * @param  string  $property
     * @return static
     */
    protected function addAddress($address, $name, $property)
    {
        $this->{$property} = array_merge(
            $this->{$property},
            $this->parseAddresses($this->arrayOfAddresses($address) ? $address : [$address => $name])
        );

        return $this;
    }

    /**
     * Parse the multi-address array into the necessary format.
     *
     * ->to('some1@address.tld')
     *
     * ->to(['some3@address.tld' => 'The Name']);
     *
     * ->to(['some2@address.tld']);
     *
     * ->to(['some4@address.tld', 'other4@address.tld']);
     *
     * ->to([
     *       'recipient-with-name@address.ltd' => 'Recipient Name One',
     *       'no-name@address.ltd',
     *       'named-recipient@address.ltd' => 'Recipient Name Two',
     *  ]);
     *
     * ->to(['name' => 'Name', 'address' => 'example@address.ltd']);
     *
     * ->to([
     *     ['name' => 'Name', 'address' => 'example@address.ltd'],
     *     ['name' => 'Name', 'address' => 'example@address.ltd']
     * ]);
     *
     * ->to([['name' => 'Name', 'address' => 'example@address.ltd'], 'example@address.ltd']);
     *
     * ->to([['address' => 'example@address.ltd']]);
     *
     * ->to([
     *      ['name' => 'Name', 'address' => 'example@address.ltd'],
     *      'example@address.ltd',
     *      ['address' => 'example@address.ltd']
     * ]);
     *
     * @param  array  $value
     * @return array
     */
    protected function parseAddresses($value)
    {
        $addresses = collect([]);

        if (count($value) === 2 && isset($value['address'])) {
            $addresses->push(['name' => $value['name'] ?? null, 'address' => $value['address']]);
        } else {
            foreach ($value as $address => $values) {
                if (! is_array($values)) {
                    if (is_numeric($address)) {
                        $addresses->push(['name' => null, 'address' => $values]);
                    } elseif (is_null($values)) {
                        $addresses->push(['name' => null, 'address' => $address]);
                    } else {
                        $addresses->push(['name' => $values, 'address' => $address]);
                    }
                } else {
                    $addresses = $addresses->merge([[
                        'name' => $values['name'] ?? null,
                        'address' => $values['address'],
                    ]]);
                }
            }
        }

        return $addresses->filter(function ($recipient) {
            return (new EmailValidator)->isValid($recipient['address'], new RFCValidation());
        })->map(function ($recipient) {
            // Make sure that the recipient name is always null
            // even when passed as empty string
            $recipient['name'] = $recipient['name'] === '' ? null : $recipient['name'];

            return $recipient;
        })->values()->all();
    }

    /**
     * Determine if the given "address" is actually an array of addresses.
     *
     * @param  mixed  $address
     * @return bool
     */
    protected function arrayOfAddresses($address)
    {
        return is_array($address) ||
               $address instanceof Arrayable ||
               $address instanceof Traversable;
    }
}
