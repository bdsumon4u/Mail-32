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

/**
 * Provides possible types of folders
 */
class FolderType
{
    const INBOX = 'inbox';

    const SENT = 'sent';

    const TRASH = 'trash';

    const DRAFTS = 'drafts';

    const SPAM = 'spam';

    const ARCHIVE = 'archive';

    const OTHER = 'other';

    /**
     * Known folder names map
     *
     * @var array
     */
    const KNOWN_FOLDER_NAME_MAP = [
        'Inbox' => self::INBOX,
        'INBOX' => self::INBOX,
        'inbox' => self::INBOX,

        'Drafts' => self::DRAFTS,
        'Draft' => self::DRAFTS, // Yahoo
        'draft' => self::DRAFTS,
        'DRAFT' => self::DRAFTS, // Gmail label id
        'INBOX.Drafts' => self::DRAFTS,
        'INBOX.drafts' => self::DRAFTS,
        '[Gmail]/Drafts' => self::DRAFTS, // Gmail IMAP
        'drafts' => self::DRAFTS, // Outlook well known name

        'Spam' => self::SPAM,
        'Junk' => self::SPAM,
        'Junk E-mail' => self::SPAM,
        'Junk Email' => self::SPAM,
        'INBOX.junk' => self::SPAM,
        'INBOX.Junk' => self::SPAM,
        'INBOX.spam' => self::SPAM,
        'INBOX.Spam' => self::SPAM,
        '[Gmail]/Spam' => self::SPAM, // Gmail IMAP
        'SPAM' => self::SPAM, // Gmail label id
        'junkemail' => self::SPAM, // Outlook well known name

        'Sent' => self::SENT,
        'SentBox' => self::SENT,
        'Sent Items' => self::SENT,
        'Sent Messages' => self::SENT,
        'INBOX.Sent' => self::SENT,
        'INBOX.sent' => self::SENT,
        '[Gmail]/Sent Mail' => self::SENT, // Gmail IMAP
        'SENT' => self::SENT, // Gmail label id
        'sentitems' => self::SENT, // Outlook well known name

        'Trash' => self::TRASH,
        'Deleted' => self::TRASH,
        'Deleted Items' => self::TRASH,
        'Deleted Messages' => self::TRASH,
        'INBOX.Trash' => self::TRASH,
        'INBOX.trash' => self::TRASH,
        '[Gmail]/Trash' => self::TRASH, // Gmail IMAP
        'TRASH' => self::TRASH, // Gmail label id
        'deleteditems' => self::TRASH, // Outlook well known name

        'Archive' => self::ARCHIVE,
        'INBOX.Archive' => self::ARCHIVE,
        'INBOX.archive' => self::ARCHIVE,
        'archive' => self::ARCHIVE, // Outlook well known name and possibly others
    ];

    /**
     * @return array
     */
    public static function outgoingTypes(): array
    {
        return [
            static::SENT,
            static::DRAFTS,
        ];
    }

    /**
     * @return array
     */
    public static function incomingTypes(): array
    {
        return [
            static::INBOX,
            static::SPAM,
        ];
    }

    /**
     * @link https://tools.ietf.org/html/rfc6154#page-3
     *
     * Get the special folder types
     *
     * @return array
     */
    public static function specialTypes(): array
    {
        return [
            self::INBOX,
            self::SENT,
            self::TRASH,
            self::DRAFTS,
            self::SPAM,
            self::ARCHIVE,
        ];
    }
}
