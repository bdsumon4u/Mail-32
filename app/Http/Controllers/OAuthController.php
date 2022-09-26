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

namespace App\Http\Controllers;

use App\Innoclapps\Facades\OAuthState;
use App\Innoclapps\OAuth\OAuthManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class OAuthController extends Controller
{
    /**
     * The route to redirect if there is an error
     *
     * @var string
     */
    protected $onErrorRedirectTo = '/dashboard';

    /**
     * Initialize OAuth Controller
     *
     * @param  \App\Innoclapps\OAuth\OAuthManager  $manager
     */
    public function __construct(protected OAuthManager $manager)
    {
    }

    /**
     * Connect OAuth Account
     *
     * @param  string  $providerName
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function connect($providerName, Request $request)
    {
        $state = $this->manager->generateRandomState();

        OAuthState::put($state);

        return redirect($this->manager->createProvider($providerName)
            ->getAuthorizationUrl(['state' => $state]));
    }

    /**
     * Callback for OAuth Account
     *
     * @param  string  $providerName
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function callback($providerName, Request $request)
    {
        if ($request->error) {
            // Got an error, probably user denied access
            return redirect($this->onErrorRedirectTo)
                ->withErrors($request->error_description ?: $request->error);
        } elseif (! OAuthState::validate($request->state)) {
            return redirect($this->onErrorRedirectTo)
                ->withErrors(__('app.oauth.invalid_state'));
        }

        if ($request->has('code')) {
            $this->manager->forUser($request->user()->id)
                ->connect($providerName, $request->code);

            $returnUrl = OAuthState::getParameter('return_url', '/mail/accounts');

            // Check if the account previously required authentication (for re-authenticate)
            if ((string) OAuthState::getParameter('re_auth') === '1') {
                Session::flash('success', __('app.oauth.re_authenticated'));
            }

            return tap(redirect($returnUrl), function () {
                // Finally, forget the oauth state, the state is used in the listeners
                // to get parameters for the actual accounts data
                OAuthState::forget();
            });
        }
    }
}
