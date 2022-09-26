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

namespace App\Innoclapps\MailClient\Imap;

use App\Innoclapps\MailClient\AbstractAttachment;

class Attachment extends AbstractAttachment
{
    /**
     * Get attachment content id
     *
     * @return string|null
     */
    public function getContentId()
    {
        $partStructure = $this->getEntity()->getStructure();

        if (isset($partStructure->id)) {
            return str_replace(['<', '>'], '', $partStructure->id);
        }

        return null;
    }

    /**
     * Get the attachment file name
     *
     * @return string
     */
    public function getFileName()
    {
        return $this->getEntity()->getFilename();
    }

    /**
     * Get the attachment content
     *
     * @return string
     */
    public function getContent()
    {
        return $this->getEntity()->getContent();
    }

    /**
     * Get the attachment content type
     *
     * @return string
     */
    public function getContentType()
    {
        return $this->getEntity()->getType().'/'.$this->getEntity()->getSubType();
    }

    /**
     * Get the attachment encoding
     *
     * @return string
     */
    public function getEncoding()
    {
        return $this->getEntity()->getEncoding();
    }

    /**
     * Check whether the attachment is inline
     *
     * @return bool
     */
    public function isInline()
    {
        $partStructure = $this->getEntity()->getStructure();

        if ($partStructure->ifdisposition) {
            return strtolower($partStructure->disposition) === 'inline';
        }

        return false;
    }

    /**
     * Get the attachment size
     *
     * @return int
     */
    public function getSize()
    {
        return $this->getEntity()->getSize();
    }

    /**
     * Check whether the attachment is embedded message
     *
     * @return bool
     */
    public function isEmbeddedMessage()
    {
        return $this->getEntity()->isEmbeddedMessage();
    }
}
