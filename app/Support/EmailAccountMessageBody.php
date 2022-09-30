<?php

namespace App\Support;

use App\Models\EmailAccountMessage;
use Illuminate\Support\Str;

class EmailAccountMessageBody
{
    protected ?string $previewText = null;

    protected $parsed = null;

    /**
     * Reply regex when email is sent via app
     */
    const REPLY_REGEX = '/(<div class="(hotash_attr|c_hotash_attr)">)(.*)(<\/div>)/mU';

    public function __construct(protected EmailAccountMessage $message)
    {
    }

    /**
     * Get the message preview text
     *
     * @return string
     */
    public function previewText()
    {
        if ($this->previewText) {
            return $this->previewText;
        }

        if (! $this->message->html_body) {
            return $this->previewText = AutoParagraph::wrap($this->message->text_body);
        }

        // TODO: Preview Text
        return 'Preview Text';
    }

    /**
     * Get the message visible text
     *
     * @return string
     */
    public function visibleText()
    {
        if ($this->message->is_sent_via_app && $this->message->isReply() &&
            preg_match(static::REPLY_REGEX, $this->previewText(), $matches)) {
            return Str::before($this->previewText(), $matches[0]);
        }

        // TODO: Visible Text
        return 'Visible Text';
    }

    /**
     * Get the message the text that should be hidden
     *
     * @return string
     */
    public function hiddenText()
    {
        if ($this->message->is_sent_via_app && $this->message->isReply() &&
        preg_match(static::REPLY_REGEX, $this->previewText(), $matches)) {
            return $matches[0].Str::after($this->previewText(), $matches[0]);
        }

        // TODO: Hidden Text
        return 'Hidden Text';
    }

    /**
     * Check whether the given message body has HTML
     *
     * @param  string  $text
     * @return string
     */
    protected function applyBodyFormats($text)
    {
        if (! preg_match('/<[^<]+>/', $text, $m) != 0) {
            return AutoParagraph::wrap($text);
        }

        // For HTML, open all external links in new tab
        return preg_replace(
            '/(<a href="https?:[^"]+")>/is',
            '\\1 target="_blank">',
            $text
        );
    }
}
