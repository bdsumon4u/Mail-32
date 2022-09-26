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

use App\Innoclapps\AbstractMask;
use App\Innoclapps\Contracts\MailClient\AttachmentInterface;

abstract class AbstractAttachment extends AbstractMask implements AttachmentInterface
{
    /**
     * Serialize
     *
     * @return array
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * toArray
     *
     * @return array
     */
    public function toArray()
    {
        return [
            'file_name' => $this->getFileName(),
            'content' => $this->getContent(),
            'content_type' => $this->getContentType(),
            'encoding' => $this->getEncoding(),
            'content_id' => $this->getContentId(),
            'size' => $this->getSize(),
            'inline' => $this->isInline(),
        ];
    }
}
