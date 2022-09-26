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

use Illuminate\Support\Collection;

class FolderCollection extends Collection
{
    /**
     * Find a folder by a given identifier
     *
     * @param  \App\Innoclapps\MailClient\FolderIdentifier  $identifier
     * @return null|\App\Innoclapps\Contracts\MailClient\FolderInterface
     */
    public function find(FolderIdentifier $identifier)
    {
        return $this->findDeep($identifier);
    }

    /**
     * Deep find a folder by a given identifier
     *
     * @param  \App\Innoclapps\MailClient\FolderIdentifier  $identifier
     * @param  \Illuminate\Support\Collection|array  $folders
     * @return null|\App\Innoclapps\Contracts\MailClient\FolderInterface
     */
    public function findDeep(FolderIdentifier $identifier, $folders = null)
    {
        $retval = null;

        $folders ??= $this->items;

        foreach ($folders as $folder) {
            $retval = $folder->{$identifier->key} == $identifier->value ?
            $folder :
            $this->findDeep($identifier, $folder->getChildren());

            if ($retval) {
                break;
            }
        }

        return $retval;
    }

    /**
     * Create tree from delimiter
     *
     * @param  null|string  $delimiter
     * @return static
     */
    public function createTreeFromDelimiter($delimiter = null)
    {
        $tree = $this->explodeTree($delimiter);

        return $this->newCollectionFromTree($tree);
    }

    /**
     * Get all folders including the child in one array
     *
     * @param  mixed  $depth [NOT APPLICABLE]
     * @return self
     */
    public function flatten($depth = INF)
    {
        $data = [];

        foreach ($this->items as $folder) {
            $data[] = $folder;
            $data = array_merge($data, $this->extractChildren($folder));
        }

        return new self($data);
    }

    /**
     * Deep extract all folder children
     *
     * @param  \App\Innoclapps\Contracts\MailClient\FolderInterface  $folder
     * @return array
     */
    protected function extractChildren($folder)
    {
        $folders = [];

        foreach ($folder->getChildren() as $child) {
            $folders[] = $child;

            if (count($child->getChildren()) > 0) {
                $folders = array_merge($folders, $this->extractChildren($child));
            }
        }

        return $folders;
    }

    /**
     * Create tree from folders delimiter
     *
     * @param  null|string  $delimiter
     * @param  bool  $includeBaseValue
     * @return array
     */
    protected function explodeTree($delimiter, $includeBaseValue = true)
    {
        $treeResult = [];

        foreach ($this->items as $folder) {
            $folderDelimiter = $this->determineTreeDelimiter($folder, $delimiter);

            if (! empty($folderDelimiter)) {
                $parts = explode($folderDelimiter, $folder->getName());
            } else {
                $parts = [$folder->getName()];
            }

            $leafPart = array_pop($parts);

            // Build parent structure
            // Might be slow for really deep and large structures
            $parentArr = &$treeResult;
            foreach ($parts as $part) {
                if (! isset($parentArr[$part])) { // @phpstan-ignore-line
                    $parentArr[$part] = [];
                } elseif (! is_array($parentArr[$part])) {
                    if ($includeBaseValue) {
                        $parentArr[$part] = ['__base_val' => $parentArr[$part]];
                    } else {
                        $parentArr[$part] = [];
                    }
                }
                $parentArr = &$parentArr[$part];
            }

            // Add the final part to the structure
            if (empty($parentArr[$leafPart])) { // @phpstan-ignore-line
                $parentArr[$leafPart] = $folder;
            } elseif ($includeBaseValue && is_array($parentArr[$leafPart])) {
                $parentArr[$leafPart]['__base_val'] = $folder;
            }
        }

        return $treeResult;
    }

    /**
     * Transform the created tree from delimiter to appropriate format
     *
     * @param  array  $tree
     * @return static
     */
    protected function newCollectionFromTree($tree)
    {
        $items = [];

        foreach ($tree as $key => $folders) {
            $hasChildren = is_array($folders);

            // Has children
            if ($hasChildren) {
                $parent = $folders['__base_val'];
                unset($folders['__base_val']);
                $parent->setChildren($this->newCollectionFromTree($folders));
                $items[] = $parent;
            } else {
                $items[] = $folders;
            }
        }

        return new static($items); // @phpstan-ignore-line
    }

    /**
     * Determine the tree delimiter
     *
     * @param  \App\Innoclapps\Contracts\MailClient\FolderInterface  $folder
     * @param  string  $default
     * @return string
     */
    protected function determineTreeDelimiter($folder, $default)
    {
        if (! method_exists($folder, 'getDelimiter')) {
            return $default;
        }

        return $folder->getDelimiter();
    }
}
