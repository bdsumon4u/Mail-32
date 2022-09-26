<?php

namespace App\Hotash;

use Illuminate\Container\Container;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Mail\Mailable as Mail;
use Illuminate\Mail\Markdown;
use Illuminate\Support\Facades\Mail as Transport;
use Illuminate\Support\Facades\View;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use InvalidArgumentException;
use ReflectionClass;
use ReflectionProperty;

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
        if (! $this->getSystemEmail()) {
            return parent::send($mailer);
        }

        // We will check if the email account requires authentication, as we
        // are not able to send emails if the account required authentication, in this case
        // we will return to the laravel default mailer behavior
        if (! $this->getSystemEmail()->canSendMails()) {
            return parent::send($mailer);
        }

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
     * Render the mailable into a view.
     *
     * @return string
     *
     * @throws \ReflectionException
     */
    public function render()
    {
        return $this->withLocale($this->locale, function () {
            Container::getInstance()->call([$this, 'build']);

            return Container::getInstance()->make('mailer')->render(
                $this->buildView(), $this->buildViewData()
            );
        });
    }

    // /**
    //  * Build the view for the message.
    //  *
    //  * @return array|string
    //  *
    //  * @throws \ReflectionException
    //  */
    // protected function buildView()
    // {
    //     if (isset($this->html)) { // @phpstan-ignore-line
    //         return array_filter([
    //             'html' => new HtmlString($this->html),
    //             'text' => $this->textView,
    //         ]);
    //     }

    //     if (isset($this->markdown)) { // @phpstan-ignore-line
    //         return $this->buildMarkdownView();
    //     }

    //     if (isset($this->view, $this->textView)) {
    //         return [$this->view, $this->textView];
    //     } elseif (isset($this->textView)) {
    //         return ['text' => $this->textView];
    //     }

    //     return $this->view;
    // }

    // /**
    //  * Build the Markdown view for the message.
    //  *
    //  * @return array
    //  *
    //  * @throws \ReflectionException
    //  */
    // protected function buildMarkdownView()
    // {
    //     $markdown = Container::getInstance()->make(Markdown::class);

    //     if (isset($this->theme)) {
    //         $markdown->theme($this->theme);
    //     }

    //     $data = $this->buildViewData();

    //     return [
    //         'html' => $markdown->render($this->markdown, $data),
    //         'text' => $this->buildMarkdownText($markdown, $data),
    //     ];
    // }

    // /**
    //  * Build the view data for the message.
    //  *
    //  * @return array
    //  *
    //  * @throws \ReflectionException
    //  */
    // public function buildViewData()
    // {
    //     $data = $this->viewData;

    //     if (static::$viewDataCallback) { // @phpstan-ignore-line
    //         $data = array_merge($data, call_user_func(static::$viewDataCallback, $this));
    //     }

    //     foreach ((new ReflectionClass($this))->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
    //         if ($property->getDeclaringClass()->getName() !== self::class) {
    //             $data[$property->getName()] = $property->getValue($this);
    //         }
    //     }

    //     return $data;
    // }

    // /**
    //  * Build the text view for a Markdown message.
    //  *
    //  * @param  \Illuminate\Mail\Markdown  $markdown
    //  * @param  array  $data
    //  * @return string
    //  */
    // protected function buildMarkdownText($markdown, $data)
    // {
    //     /** @phpstan-ignore-next-line */
    //     return $this->textView ?? $markdown->renderText($this->markdown, $data);
    // }

    // /**
    //  * Get the mailable human readable name
    //  *
    //  * @return string
    //  */
    // public static function name()
    // {
    //     return Str::title(Str::snake(class_basename(get_called_class()), ' '));
    // }

    // /**
    //  * Build the view for the message.
    //  *
    //  * @return array
    //  */
    // protected function buildView()
    // {
    //     $renderer = $this->getMailableTemplateRenderer();

    //     return array_filter([
    //         'html' => new HtmlString($renderer->renderHtmlLayout()),
    //         'text' => new HtmlString($renderer->renderTextLayout()),
    //     ]);
    // }

    // /**
    //  * Build the view data for the message.
    //  *
    //  * @return array
    //  */
    // public function buildViewData()
    // {
    //     return $this->placeholders()?->parse() ?: parent::buildViewData();
    // }

    // /**
    //  * Get the mailable template subject
    //  *
    //  * @return string|null
    //  */
    // protected function getMailableTemplateSubject()
    // {
    //     if ($this->subject) {
    //         return $this->subject;
    //     }

    //     return $this->getMailableTemplate()->getSubject() ?? $this->name();
    // }

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

    // /**
    //  * Get the mail template repository
    //  *
    //  * @return \App\Innoclapps\Contracts\Repositories\MailableRepository
    //  */
    // protected static function templateRepository()
    // {
    //     return resolve(MailableRepository::class);
    // }

    // /**
    //  * Prepares alt text message from HTML
    //  *
    //  * @param  string  $html
    //  * @return string
    //  */
    // protected static function prepareTextMessageFromHtml($html)
    // {
    //     return Html2Text::convert($html);
    // }

    // /**
    //  * Get the mail template content rendered
    //  *
    //  * @return \App\Innoclapps\MailableTemplates\Renderer
    //  */
    // protected function getMailableTemplateRenderer(): Renderer
    // {
    //     return app(Renderer::class, [
    //         'htmlTemplate' => $this->getMailableTemplate()->getHtmlTemplate(),
    //         'subject' => $this->getMailableTemplateSubject(),
    //         'placeholders' => $this->placeholders(),
    //         'htmlLayout' => $this->getHtmlLayout() ?? config('innoclapps.mailables.layout'),
    //         'textTemplate' => $this->getMailableTemplate()->getTextTemplate(),
    //         'textLayout' => $this->getTextLayout(),
    //     ]);
    // }

    // /**
    //  * Get the mailable HTML layout
    //  *
    //  * @return null
    //  */
    // public function getHtmlLayout()
    // {
    //     return null;
    // }

    // /**
    //  * Get the mailable text layout
    //  *
    //  * @return null
    //  */
    // public function getTextLayout()
    // {
    //     return null;
    // }

    // /**
    //  * Provide the defined mailable template placeholders
    //  *
    //  * @return \App\Innoclapps\MailableTemplates\Placeholders\Collection|null
    //  */
    // public function placeholders()
    // {
    //     //
    // }

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

    // /**
    //  * Seed the mailable in database as mail template
    //  *
    //  * @param  string  $locale Locale to seed the mail template
    //  * @return \App\Innoclapps\Models\MailableTemplate
    //  */
    // public static function seed($locale = 'en')
    // {
    //     $default = static::default();
    //     $mailable = get_called_class();
    //     $textTemplate = $default->textMessage() ?? static::prepareTextMessageFromHtml($default->htmlMessage());

    //     $template = static::templateRepository()->firstOrNew(
    //         [
    //             'locale' => $locale,
    //             'mailable' => $mailable,
    //         ],
    //         [
    //             'locale' => $locale,
    //             'mailable' => $mailable,
    //             'subject' => $default->subject(),
    //             'html_template' => $default->htmlMessage(),
    //             'text_template' => $textTemplate,
    //         ]
    //     );

    //     return tap($template, function ($instance) use ($mailable) {
    //         if (! $instance->getKey()) {
    //             $instance->mailable = $mailable;
    //             $instance->name = static::name();

    //             $instance->save();
    //         }
    //     });
    // }
}
