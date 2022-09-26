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

use App\Innoclapps\Google\Concerns\HasDecodeableBody;
use App\Innoclapps\Google\Concerns\HasHeaders;
use App\Innoclapps\Google\Services\Service;
use App\Innoclapps\Mail\Headers\HeadersCollection;
use Google_Service_Gmail;
use Google_Service_Gmail_MessagePart;

/** @property Google_Service_Gmail $service */
class Attachment extends Service
{
    use HasDecodeableBody,
        HasHeaders;

    /**
     * The attachment content
     *
     * @var string|null
     */
    protected $content;

    /**
     * Attachment constructor.
     *
     * @param $messageId
     * @param  \Google_Service_Gmail_MessagePart  $part
     */
    public function __construct($client, protected string $messageId, protected Google_Service_Gmail_MessagePart $part)
    {
        parent::__construct($client, Google_Service_Gmail::class);

        $this->headers = new HeadersCollection;

        foreach ($part->getHeaders() as $header) {
            $this->headers->pushHeader($header->getName(), $header->getValue());
        }
    }

    /**
     * Retuns attachment ID
     *
     * @return string
     */
    public function getId()
    {
        return $this->part->getBody()->getAttachmentId();
    }

    /**
     * Get the attachment content id
     *
     * Available only for inline attachments with CID (Content-ID)
     *
     * @return string|null
     */
    public function getContentId()
    {
        $contentId = $this->getHeaderValue('content-id');

        if (! $contentId) {
            $contentId = $this->getHeaderValue('x-attachment-id');
        }

        return ! is_null($contentId) ? str_replace(['<', '>'], '', $contentId) : null;
    }

    /**
     * Returns attachment file name
     *
     * @return string
     */
    public function getFileName()
    {
        return $this->part->getFilename();
    }

    /**
     * Returns mime type of the attachment
     *
     * @return string
     */
    public function getMimeType()
    {
        return $this->part->getMimeType();
    }

    /**
     * Checks whether the attachments is inline
     *
     * @return bool
     */
    public function isInline()
    {
        if ($this->getHeaderValue('content-id') || $this->getHeaderValue('x-attachment-id')) {
            return true;
        }

        return str_contains($this->getHeaderValue('content-disposition'), 'inline');
    }

    /**
     * Get the attachment encoding
     *
     * @return string|null
     */
    public function getEncoding()
    {
        return $this->getHeaderValue('content-transfer-encoding');
    }

    /**
     * Returns approximate size of the attachment
     *
     * @return mixed
     */
    public function getSize()
    {
        return $this->part->getBody()->getSize();
    }

    /**
     * Get the attachment content
     *
     * @return string
     */
    public function getContent()
    {
        // Perhaps the content is set e.q. via preloaded attachments?
        // or the method already fetched the content
        if (! is_null($this->content)) {
            return $this->content;
        }

        $attachment = $this->getAttachment();

        return $this->content = $this->getDecodedBody($attachment->getData());
    }

    /**
     * Set the attachment content
     *
     * @param  string  $content
     * @return self
     */
    public function setContent($content)
    {
        $this->content = $this->getDecodedBody($content);

        return $this;
    }

    /**
     * Get the attachment from Gmail API
     *
     * @return \Google_Service_Gmail_MessagePartBody
     */
    protected function getAttachment()
    {
        return $this->service->users_messages_attachments->get(
            'me',
            $this->messageId,
            $this->getId()
        );
    }
}
