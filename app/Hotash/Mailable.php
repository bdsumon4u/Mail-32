<?php

namespace App\Hotash;

use App\Enums\ConnectionType;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Mail\Mailable as Mail;
use Illuminate\Mail\Mailer;
use Illuminate\Support\Facades\Mail as Transport;
use Illuminate\Support\Facades\View;
use InvalidArgumentException;
use Symfony\Component\Mailer\Transport\Dsn;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransportFactory;

class Mailable extends Mail
{
    use SystemEmail;

    /**
     * Send the message using the given mailer.
     *
     * @param  \Illuminate\Contracts\Mail\Factory|\Illuminate\Contracts\Mail\Mailer  $mailer
     * @return \Illuminate\Mail\SentMessage|null
     */
    public function send($mailer)
    {
        // Check if there is no system email account selected to send
        // mail from, in this case, use the Laravel default configuration
        if (! $email = $this->getSystemEmail()) {
            return parent::send($mailer);
        }

        // We will check if the email account requires authentication, as we
        // are not able to send emails if the account required authentication, in this case
        // we will return to the laravel default mailer behavior
        if (! $email->canSendMails()) {
            return parent::send($mailer);
        }

        if ($email->connection_type === ConnectionType::Imap) {
            $encryption = $email->getSmtpConfig()->encryption() ?: '';
            $username = $email->getSmtpConfig()->username() ?? $email->getSmtpConfig()->email();

            $factory = new EsmtpTransportFactory;

            $transport = $factory->create(new Dsn(
                ! empty($encryption) && $encryption === 'tls' ? (($email->getSmtpConfig()->port() == 465) ? 'smtps' : 'smtp') : '',
                $email->getSmtpConfig()->host(),
                $username,
                $email->getSmtpConfig()->password(),
                $email->getSmtpConfig()->port()
            ));

            return parent::send(new Mailer('smtp', app()->get('view'), $transport, app()->get('events')));
        }
        dd($email->connection_type);

        return parent::send(Transport::mailer('hotash'));
    }

    /**
     * Parse the given view name or array.
     *
     * @param  string|array  $view
     * @return array
     *
     * @throws \InvalidArgumentException
     */
    protected function parseView($view)
    {
        if (is_string($view)) {
            return [$view, null, null];
        }

        // If the given view is an array with numeric keys, we will just assume that
        // both a "pretty" and "plain" view were provided, so we will return this
        // array as is, since it should contain both views with numerical keys.
        if (is_array($view) && isset($view[0])) {
            return [$view[0], $view[1], null];
        }

        // If this view is an array but doesn't contain numeric keys, we will assume
        // the views are being explicitly specified and will extract them via the
        // named keys instead, allowing the developers to use one or the other.
        if (is_array($view)) {
            return [
                $view['html'] ?? null,
                $view['text'] ?? null,
                $view['raw'] ?? null,
            ];
        }

        /** @phpstan-ignore-next-line */
        throw new InvalidArgumentException('Invalid view.');
    }

    /**
     * Render the given view.
     *
     * @param  string  $view
     * @param  array  $data
     * @return string
     */
    protected function renderView($view, $data)
    {
        if (! $view) {
            return $view;
        }

        /** @phpstan-ignore-next-line */
        return $view instanceof Htmlable
            ? $view->toHtml()
            : view($view, $data)->render();
    }

    /**
     * Build the mailable attachemnts via email client
     *
     * @param  \App\Innoclapps\MailClient\Client  $client
     * @return static
     */
    protected function buildAttachmentsViaEmailClient($client)
    {
        foreach ($this->attachments as $attachment) {
            $client->attach($attachment['file'], $attachment['options']);
        }

        foreach ($this->rawAttachments as $attachment) {
            $client->attachData(
                $attachment['data'],
                $attachment['name'],
                $attachment['options']
            );
        }

        $client->diskAttachments = $this->diskAttachments;

        return $this;
    }

    /**
     * The Mailable build method
     *
     * @see  buildSubject, buildView, send
     *
     * @return static
     */
    public function build()
    {
        return $this;
    }
}
