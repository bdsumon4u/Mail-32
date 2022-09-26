<?php

namespace App\Support;

use App\Models\EmailAccountMessage;
use EmailReplyParser\Parser\EmailParser;
use Illuminate\Support\Str;
use KubAT\PhpSimple\HtmlDomParser;
use Pelago\Emogrifier\CssInliner;
use Pelago\Emogrifier\HtmlProcessor\CssToAttributeConverter;
use Pelago\Emogrifier\HtmlProcessor\HtmlPruner;

class EmailAccountMessageBody
{
    protected ?string $previewText = null;

    /*?EmailReplyParser\Email*/
    protected $parsed = null;

    /**
     * Reply regex when email is sent via app
     */
    const REPLY_REGEX = '/(<div class="(concord_attr|c_concord_attr)">)(.*)(<\/div>)/mU';

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

        return 'Preview Text'; // HOTASH #
        // $cssInliner = CssInliner::fromHtml(
        //     $this->applyBodyFormats($this->message->html_body)
        // )->inlineCss();

        // $domDocument = $cssInliner->getDomDocument();
        // HtmlPruner::fromDomDocument($domDocument)->removeElementsWithDisplayNone();

        // $finalHtml = CssToAttributeConverter::fromDomDocument($domDocument)
        //     ->convertCssToVisualAttributes()->renderBodyContent();

        // return $this->previewText = Str::clickable($this->prefixStaleClasses($finalHtml));
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

        return 'Visible Text'; // HOTASH #
        // $visibleText = $this->parseMessageForPreview()->getVisibleText();

        // if (empty($visibleText)) {
        //     return $this->previewText();
        // }

        // return $this->applyBodyFormats($visibleText);
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

        return 'Hidden Text'; // HOTASH #
        // $fragments = $this->parseMessageForPreview()->getFragments();

        // $hiddenFragments = array_filter($fragments, function ($fragment) {
        //     return $fragment->isHidden();
        // });

        // return $this->applyBodyFormats(rtrim(implode("\n", $hiddenFragments)));
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

    // HOTASH #
    // /**
    //  * Parse the message with the EmailReplyParser
    //  *
    //  * @return \EmailReplyParser\Email
    //  */
    // protected function parseMessageForPreview()
    // {
    //     if ($this->parsed) {
    //         return $this->parsed;
    //     }

    //     if (! $this->message->html_body) {
    //         return (new EmailParser)->parse($this->message->text_body ?? '');
    //     }

    //     // Encode any entities to UTF-8 as the CssInliner expects UTF-8 encoded string
    //     $cssInliner = CssInliner::fromHtml(
    //         mb_convert_encoding($this->message->html_body, 'HTML-ENTITIES', 'UTF-8')
    //     )->inlineCss();

    //     $domDocument = $cssInliner->getDomDocument();
    //     HtmlPruner::fromDomDocument($domDocument)->removeElementsWithDisplayNone()
    //         ->removeRedundantClassesAfterCssInlined($cssInliner);

    //     $finalHtml = CssToAttributeConverter::fromDomDocument($domDocument)
    //         ->convertCssToVisualAttributes()->renderBodyContent();

    //     return $this->parsed = (new EmailParser)->parse($this->prefixStaleClasses($finalHtml) ?? '');
    // }

    // /**
    //  * Prefix stale classes
    //  *
    //  * Usually when the HTML is parsed via the emogrifier, if the emogrifier is unable to
    //  * extract the classes CSS style into inline styles, the classes are left as they were in the element
    //  * however, stale general classes e.q. block, text-left may cause issues with alignment because they
    //  * already exists as Tailwind CSS classses
    //  *
    //  * @param  string  $html
    //  * @param  string  $prefix
    //  * @return string
    //  */
    // protected function prefixStaleClasses($html, string $prefix = 'c_')
    // {
    //     if (empty($html)) {
    //         return $html;
    //     }

    //     $dom = HtmlDomParser::str_get_html($html);

    //     foreach ($dom->find('*[class]') as $element) {
    //         $class = $element->getAttribute('class');

    //         if (empty(trim($class))) {
    //             continue;
    //         }

    //         $value = explode(' ', $class);
    //         array_walk($value, 'trim');

    //         $element->setAttribute('class', implode(' ', array_map(fn ($class) => $prefix.$class, $value)));
    //     }

    //     return $dom->save();
    // }
    // HOTASH #
}
