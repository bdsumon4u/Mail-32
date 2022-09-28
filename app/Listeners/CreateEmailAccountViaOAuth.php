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

use App\Enums\ConnectionType;
use App\Enums\EmailAccountType;
use App\Innoclapps\Facades\OAuthState;
use App\Innoclapps\MailClient\ClientManager;
use App\Models\EmailAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class CreateEmailAccountViaOAuth
{
    /**
     * Initialize new CreateEmailAccountViaOAuth instance.
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function __construct(protected Request $request)
    {
    }

    /**
     * Handle Microsoft email account connection finished
     *
     * @param  object  $event
     * @return void
     */
    public function handle($event)
    {
        tap($event->account, function ($oAuthAccount) {
            $account = EmailAccount::query()->where('email', $oAuthAccount->email)->first();

            // Connection not intended for email account
            // Connection can be invoke via /oauth/accounts route or calendar because of re-authentication
            $emailAccountBeingConnected = ! is_null(OAuthState::getParameter('email_account_type'));

            if (! $emailAccountBeingConnected) {
                // We will check if this OAuth account actually exists and if yes,
                // we will make sure that the account is usable and it does not require authentication in database
                // as well that sync is enabled in case stopped previously e.q. because of refresh token
                // in this case, the user won't need to re-authenticate via the email accounts index area again
                if ($account) {
                    $account->forceFill(['o_auth_account_id' => $oAuthAccount->id])->save(); // HOTASH # Custom

                    $this->makeSureAccountIsUsable($account);
                }

                return;
            }

            if (! $account) {
                $account = $this->createEmailAccount($oAuthAccount);
            } elseif ((string) OAuthState::getParameter('re_auth') !== '1') {
                Session::flash('warning', __('mail.account.already_connected'));
            }

            $account->forceFill(['o_auth_account_id' => $oAuthAccount->id])->save(); // HOTASH # Custom
            $this->makeSureAccountIsUsable($account);

            // Update the access_token_id because it's not set in the createEmailAccount method
            $account->update(['access_token_id' => $oAuthAccount->id]);
        });
    }

    /**
     * Make sure that the account is usable
     * Sets requires autentication to false as well enabled sync again if is stopped by system
     *
     * @param  \App\Models\EmailAccount  $account
     * @return void
     */
    protected function makeSureAccountIsUsable($account)
    {
        $account->oAuthAccount->setRequiresAuthentication(false);

        // If the sync is stopped, probably it's because of empty refresh token or
        // failed authenticated for some reason, when reconnected, enable sync again
        if ($account->isSyncStoppedBySystem()) {
            $account->enableSync($account->id);
        }
    }

    /**
     * Create the email account
     *
     * @param  \App\Innoclapps\Models\OAuthAccount  $oAuthAccount
     * @return \App\Models\EmailAccount
     */
    protected function createEmailAccount($oAuthAccount)
    {
        $payload = [
            'connection_type' => $oAuthAccount->type == 'microsoft' ?
                ConnectionType::Outlook :
                ConnectionType::Gmail,
            'email' => $oAuthAccount->email,
        ];

        $remoteFolders = ClientManager::createClient(
            $payload['connection_type'],
            $oAuthAccount->tokenProvider()
        )->getImap()->getFolders();

        $payload['folders'] = $remoteFolders->toArray();
        $payload['initial_sync_from'] = OAuthState::getParameter('period');

        // if ($this->isPersonal()) { // HOTASH #
        //     $payload['user_id'] = $this->request->user()->id;
        // }

        $account = EmailAccount::create($payload);

        // if (! isset($payload['user_id'])) { // HOTASH #
        //     $fromName = ($payload['from_name_header'] ?? '') ?: EmailAccount::DEFAULT_FROM_NAME_HEADER;
        //     $account->setMeta('from_name_header', $fromName);
        // }

        $account->persistForAccount($payload['folders']);

        foreach (['trash', 'sent'] as $folderType) {
            if ($folder = $account->folders->firstWhere('type', $folderType)) {
                tap($account, function ($instance) use ($folder, $folderType) {
                    $instance->{$folderType.'Folder'}()->associate($folder);
                })->save();
            }
        }

        return $account;
    }

    /**
     * Check whether the account is personal
     *
     * @return bool
     */
    protected function isPersonal(): bool
    {
        return EmailAccountType::tryFrom(OAuthState::getParameter('email_account_type')) === EmailAccountType::PERSONAL;
    }
}
