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

namespace App\Innoclapps\MailClient\Outlook;

use App\Innoclapps\MailClient\FolderCollection;

trait MasksFolders
{
    /**
     * Ignored folders by well known name property fromm Microsoft
     *
     * @var array
     */
    protected $ignoredByWellKnownName = [
        'clutter',
        'conflicts',
        'conversationhistory',
        'outbox', // https://www.techwalla.com/articles/what-is-the-outbox-in-microsoft-outlook
        'recoverableitemsdeletions', // after deleted from the DELETE folder
        'scheduled',
        'syncissues',
    ];

    /**
     * Mask folders
     *
     * @param  array  $folders
     * @return \App\Innoclapps\MailClient\FolderCollection
     */
    protected function maskFolders($folders)
    {
        return (new FolderCollection($folders))->map(function ($folder) {
            return $this->maskFolder($folder);
        })->reject(function ($folder) {
            // Email account draft folders are not supported
            return in_array($folder->getWellKnownName(), $this->ignoredByWellKnownName) || $folder->isDraft();
        })->values();
    }

    /**
     * Mask folder
     *
     * @param  mixed  $folder
     * @return \App\Innoclapps\MailClient\Outlook\Folder
     */
    protected function maskFolder($folder)
    {
        return new Folder($folder);
    }
}
