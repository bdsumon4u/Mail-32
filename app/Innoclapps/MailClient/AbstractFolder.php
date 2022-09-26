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
use App\Innoclapps\Contracts\MailClient\FolderInterface;
use Exception;
use Illuminate\Support\Str;

abstract class AbstractFolder extends AbstractMask implements FolderInterface
{
    /**
     * The folder children
     *
     * @var \App\Innoclapps\MailClient\FolderCollection|array|null
     */
    protected $children;

    /**
     * Try to guess folder type using known special folder names.
     *
     * @return string|null
     */
    public function getType()
    {
        $map = FolderType::KNOWN_FOLDER_NAME_MAP;

        if (array_key_exists($this->getName(), $map)) {
            return $map[$this->getName()];
        } elseif (array_key_exists($this->getId(), $map)) {
            return $map[$this->getId()];
        }

        return null;
    }

    /**
     * Check whether the folder is inbox
     *
     * @return bool
     */
    public function isInbox()
    {
        return $this->getType() === FolderType::INBOX;
    }

    /**
     * Check whether the folder is draft
     *
     * @return bool
     */
    public function isDraft()
    {
        return $this->getType() === FolderType::DRAFTS;
    }

    /**
     * Check whether the folder is sent
     *
     * @return bool
     */
    public function isSent()
    {
        return $this->getType() === FolderType::SENT;
    }

    /**
     * Check whether the folder is spam
     *
     * @return bool
     */
    public function isSpam()
    {
        return $this->getType() === FolderType::SPAM;
    }

    /**
     * Check whether the folder is trash
     *
     * @return bool
     */
    public function isTrash()
    {
        return $this->getType() === FolderType::TRASH;
    }

    /**
     * Check whether the folder is trash or spam
     *
     * @return bool
     */
    public function isTrashOrSpam()
    {
        return $this->isTrash() || $this->isSpam();
    }

    /**
     * Get the folder unique identiier
     *
     * @return \App\Innoclapps\MailClient\FolderIdentifier
     */
    public function identifier()
    {
        return new FolderIdentifier('id', $this->getId());
    }

    /**
     * Set children.
     *
     * @param  \App\Innoclapps\MailClient\FolderCollection|array  $children
     * @return static
     */
    public function setChildren($children = [])
    {
        $this->children = $children;

        return $this;
    }

    /**
     * Get folder children.
     *
     * @return \App\Innoclapps\MailClient\FolderCollection|array
     */
    public function getChildren()
    {
        return $this->children ?? [];
    }

    /**
     * Check whether the folder has child folders
     *
     * @return bool
     */
    public function hasChildren()
    {
        return count($this->getChildren()) > 0;
    }

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
     * Check whether a message can be moved to this folder
     *
     * @return bool
     */
    public function supportMove()
    {
        return $this->isSelectable();
    }

    /**
     * toArray
     *
     * @return array
     */
    public function toArray()
    {
        return [
            'remote_id' => $this->getId(),
            'name' => $this->getName(),
            'display_name' => $this->getDisplayName(),
            'selectable' => $this->isSelectable(),
            'support_move' => $this->supportMove(),
            'children' => $this->getChildren(),
            'type' => $this->getType(),
        ];
    }

    /**
     * __get magic method
     *
     * @param  string  $name
     * @return mixed
     */
    public function __get($name)
    {
        $accessMethod = 'get'.Str::studly($name);

        if (method_exists($this, $accessMethod)) {
            return $this->{$accessMethod}();
        }

        throw new Exception("Property [{$name}] does not exist on this folder.");
    }
}
