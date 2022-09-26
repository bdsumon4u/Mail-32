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

namespace App\Listeners;

use App\Innoclapps\Facades\ChangeLogger;
use App\Events\EmailAccountMessageCreated;
use App\Contracts\Repositories\ContactRepository;

class CreateContactFromEmailAccountMessage
{
    /**
     * Cached accounts
     *
     * @var array
     */
    protected static array $accounts = [];

    /**
     * Initialize new CreateContactFromEmailAccountMessage instance.
     *
     * @param \App\Contracts\Repositories\ContactRepository $repository
     */
    public function __construct(protected ContactRepository $repository)
    {
    }

    /**
     * When a message is created, try to associate the
     * message with the actual contact if exists in database
     *
     * @param object $event
     * @return void
     */
    public function handle(EmailAccountMessageCreated $event)
    {
        if (! $addresses = $this->getAddressHeadersForCreation($event->message)) {
            return;
        }

        // We don't create contacts from bounce messages
        if ($event->remoteMessage->isBounce()) {
            return;
        }

        ChangeLogger::asSystem();

        // Unguard for created_by
        $this->repository->unguarded(function ($repository) use ($event, $addresses) {
            foreach ($addresses as $header) {
                $contact = $repository->create([
                    'email'      => $header->address,
                    'first_name' => $this->determineFirstName($header),
                    'last_name'  => $this->determineLastName($header),
                    'created_by' => $event->message->account->created_by,
                ]);

                $this->repository->attach($contact->id, 'emails', $event->message->id);
            }
        });

        ChangeLogger::asSystem(false);
    }

    /**
     * Check whether a contact should be created
     *
     * @param \App\Models\EmailAccountMessage $message
     *
     * @return boolean|\Illuminate\Support\Collection
     */
    protected function getAddressHeadersForCreation($message)
    {
        // User don't want to create contacts for this message account
        if ($this->getMessageAccount($message)->create_contact === false) {
            return false;
        }

        // There is no FROM and TO, we cannot determine if the contact exists
        // perhaps draft without any address headers?
        if (! $message->from && $message->to->isEmpty()) {
            return false;
        }

        // Let's check if this is a message sent from the configured account
        // If it is, we will be checking the TO header so we can create a contact(s)
        if ($message->from->address === $this->getMessageAccount($message)->email) {
            // Draft without TO headers
            if ($message->to->isEmpty()) {
                return false;
            }

            return $this->filterHeadersForCreation($message->to, $message);
        }

        // Should not happen, but let's check it for all cases
        if (is_null($message->from)) {
            return false;
        }

        // The email is sent from the contact
        return $this->filterHeadersForCreation(collect([$message->from]), $message);
    }

    /**
     * Filter the headers for createion
     * Reject's existing contacts
     *
     * @param \Illuminate\Support\Collection $headers
     * @param \App\Models\EmailAccountMessage $message $message
     * @return \Illuminate\Support\Collection
     */
    protected function filterHeadersForCreation($headers, $message)
    {
        return $headers->reject(function ($header) use ($message) {
            // The header has no address?
            if (is_null($header->address)) {
                return true;
            }

            // This checks the TO and the FROM header
            // If the address is equal as the email account address,
            // no need to do anything, reject the header address
            if ($this->getMessageAccount($message)->email === $header->address) {
                return true;
            }

            return ! is_null($this->repository->findByEmail($header->address));
        })->values();
    }

    /**
     * Determine the contact first name
     *
     * @param \App\Models\EmailAccountMessageHeader $addressHeader
     *
     * @return string
     */
    protected function determineFirstName($addressHeader)
    {
        if ($this->isAddressEqualAsName($addressHeader)) {
            return $addressHeader->address;
        }

        $nameArray = explode(' ', $addressHeader->name);

        // The name can be empty e.q. in this case we will use the email address
        // as first name, this can happen e.q. when sending the email directly from the admin area
        // and only email address is entered in the TO field, without a name
        return empty($nameArray[0]) ? $addressHeader->address : $nameArray[0];
    }

    /**
     * Determine the contact last name
     *
     * @param \App\Models\EmailAccountMessageHeader $addressHeader
     *
     * @return string|null
     */
    protected function determineLastName($addressHeader)
    {
        if ($this->isAddressEqualAsName($addressHeader)) {
            return null;
        }

        $nameArray = explode(' ', $addressHeader->name);
        // Removes the first key as it's casted as first name
        // and the left keys, are separate by space as last name
        return trim(implode(' ', array_slice($nameArray, 1)));
    }

    /**
     * Check whether the address is equal as the name header
     *
     * @param \App\Models\EmailAccountMessageHeader $addressHeader
     *
     * @return boolean
     */
    protected function isAddressEqualAsName($addressHeader)
    {
        return $addressHeader->address === $addressHeader->name;
    }

    /**
     * Get the message account
     *
     * @param \App\Models\EmailAccountMessage $message
     *
     * @return \App\Models\EmailAccount
     */
    protected function getMessageAccount($message)
    {
        if (array_key_exists($message->email_account_id, static::$accounts)) {
            return static::$accounts[$message->email_account_id];
        }

        return static::$accounts[$message->email_account_id] = $message->account;
    }
}
