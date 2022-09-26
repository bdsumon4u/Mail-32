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

use App\Innoclapps\Contracts\MailClient\FolderInterface;
use App\Innoclapps\Contracts\MailClient\ImapInterface;

abstract class AbstractImapClient implements ImapInterface
{
    /**
     * @var \App\Innoclapps\MailClient\FolderCollection
     */
    protected $availableFolders;

    /**
     * Holds the custom set sent folder
     *
     * @var \App\Innoclapps\Contracts\MailClient\FolderInterface
     */
    protected $sentFolder;

    /**
     * Holds the custom set trash folder
     *
     * @var \App\Innoclapps\Contracts\MailClient\FolderInterface
     */
    protected $trashFolder;

    /**
     * Set the IMAP account sent folder
     *
     * @param  \App\Innoclapps\Contracts\MailClient\FolderInterface  $folder
     * @return $this
     */
    public function setSentFolder(FolderInterface $folder)
    {
        $this->sentFolder = $folder;

        return $this;
    }

    /**
     * Set the IMAP account trash folder
     *
     * @param  \App\Innoclapps\Contracts\MailClient\FolderInterface  $folder
     * @return $this
     */
    public function setTrashFolder(FolderInterface $folder)
    {
        $this->trashFolder = $folder;

        return $this;
    }

    /**
     * Get the account sent folder
     *
     * @return \App\Innoclapps\Contracts\MailClient\FolderInterface|null
     */
    public function getSentFolder()
    {
        if ($this->sentFolder) { // @phpstan-ignore-line
            return $this->sentFolder;
        }

        // @phpstan-ignore-next-line
        foreach ($this->getFolders()->flatten() as $folder) {
            if ($folder->isSent()) {
                return $folder;
            }
        }
    }

    /**
     * Get the account sent folder
     *
     * @return \App\Innoclapps\Contracts\MailClient\FolderInterface|null
     */
    public function getTrashFolder()
    {
        if ($this->trashFolder) { // @phpstan-ignore-line
            return $this->trashFolder;
        }

        // @phpstan-ignore-next-line
        foreach ($this->getFolders()->flatten() as $folder) {
            if ($folder->isTrash()) {
                return $folder;
            }
        }
    }

    /**
     * Get the account folders
     *
     * @return \App\Innoclapps\MailClient\FolderCollection
     */
    public function getFolders()
    {
        if ($this->availableFolders) { // @phpstan-ignore-line
            return $this->availableFolders;
        }

        // @phpstan-ignore-next-line
        return $this->availableFolders = $this->retrieveFolders();
    }

    /**
     * Helper function to get the latest sent message but actually
     * compare the message with the values passed to identify
     * if the message is equal like the one we need
     *
     * When sending a message, reply, Microsoft does not return
     * the new message, we need to fetch the message from the sent folder
     * But in many cases, when the first request is sent, Microsoft haven't
     * sent the message yet, in this case, we will make max 5 requests to check
     * if the message has been sent and return it to the front-end
     *
     * Ugly, but works
     *
     * @param  string  $subject The original message subject
     * @param  string  $fromAddress From Email
     * @param  array  $to To Emails
     * @param  string  $messageId Internet Message ID
     * @return \App\Innoclapps\Contracts\MailClient\MessageInterface|null
     */
    public function getLatestSentMessageAndStrictCompare($subject, $fromAddress, $to, $messageId)
    {
        $messageId = str_replace(['<', '>'], '', $messageId);

        // In case associative array passed
        // map the addresses into the single single
        $toAddresses = array_map(function ($value) {
            if (isset($value['address'])) {
                return $value['address'];
            }

            return $value;
        }, $to);

        $tries = 0;

        do {
            // When the tries are bigger then 3
            // this means that 3 requests are made and we haven't found
            // the latest sent message yet
            // in this case, allow some more time between the requests
            if ($tries > 3) {
                sleep(1);
            }

            if ($message = $this->getLatestSentMessage()) {
                if ($message->getSubject() === $subject &&
                $message->getFrom()->getAddress() === $fromAddress &&
                $message->getMessageId() === $messageId) {
                    // Next, we will compare the to addresses
                    $totalToMatching = 0;
                    $messageSentTo = $message->getTo()->getAll();

                    foreach ($messageSentTo as $address) {
                        if (in_array($address['address'], $toAddresses)) {
                            $totalToMatching++;
                        }
                    }

                    // Found matching message
                    if ($totalToMatching === count($toAddresses)) {
                        return $message;
                    }
                }
            }

            $tries++;
        } while ($tries < 5);
    }
}
