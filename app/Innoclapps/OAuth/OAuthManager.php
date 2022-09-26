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

namespace App\Innoclapps\OAuth;

use App\Innoclapps\Google\OAuth\GoogleProvider;
use App\Innoclapps\Microsoft\OAuth\MicrosoftProvider;
use App\Innoclapps\OAuth\Events\OAuthAccountConnected;
use App\Models\OAuthAccount;
use League\OAuth2\Client\Grant\RefreshToken;

class OAuthManager
{
    /**
     * @var null|int
     */
    protected $userId;

    /**
     * Set the application user the token is related to
     *
     * @param  int  $userId
     * @return static
     */
    public function forUser($userId)
    {
        $this->userId = $userId;

        return $this;
    }

    /**
     * Generates random state
     *
     * @param  int  $length
     * @return string
     */
    public function generateRandomState($length = 32)
    {
        return bin2hex(random_bytes($length / 2));
    }

    /**
     * Connect OAuth account
     *
     * @param  string  $type
     * @param  string  $code
     * @return \App\Models\OAuthAccount
     */
    public function connect($type, $code)
    {
        $provider = $this->createProvider($type);

        // Get an access token using the authorization code grant
        $accessToken = $provider->getAccessToken('authorization_code', [
            'code' => $code,
        ]);

        // Let's now get the owner details
        $user = $provider->getResourceOwner($accessToken);

        return tap($this->storeAccount($type, $accessToken, $user), function ($instance) {
            event(new OAuthAccountConnected($instance));
        });
    }

    /**
     * Get the access token by email
     *
     * @param  string  $type
     * @param  string  $email
     * @return string
     */
    public function retrieveAccessToken($type, $email)
    {
        $account = $this->getAccount($type, $email);

        // Check if token is expired
        // Get current time + 5 minutes (to allow for time differences)
        if ($account->expires <= time() + 300) {
            // Token is expired (or very close to it) so let's refresh
            $newToken = $this->refreshToken($type, $account->refresh_token);

            return tap($newToken->getToken(), function ($refreshedToken) use ($newToken, $account) {
                $account->update([
                    'access_token' => $refreshedToken,
                    'expires' => $newToken->getExpires(),
                ]);
            });
        }

        // Token is still valid, just return it
        return $account->access_token;
    }

    /**
     * Create OAuth Provider
     *
     * @param  string  $type
     * // HOTASH # return \App\Innoclapps\Contracts\OAuth\Provider
     */
    public function createProvider($type)
    {
        return $this->{'create'.ucfirst($type).'Provider'}();
    }

    /**
     * Create Google OAuth Provider
     *
     * @return \App\Innoclapps\Google\OAuth\GoogleProvider
     */
    public function createGoogleProvider()
    {
        return new GoogleProvider([
            'clientId' => config('innoclapps.google.client_id'),
            'clientSecret' => config('innoclapps.google.client_secret'),
            'redirectUri' => url(config('innoclapps.google.redirect_uri')),
            'accessType' => config('innoclapps.google.access_type'),
            'scopes' => config('innoclapps.google.scopes'),
        ]);
    }

    /**
     * Create Microsoft OAuth Provider
     *
     * @return \App\Innoclapps\Microsoft\OAuth\MicrosoftProvider
     */
    public function createMicrosoftProvider()
    {
        return new MicrosoftProvider([
            'clientId' => config('innoclapps.microsoft.client_id'),
            'clientSecret' => config('innoclapps.microsoft.client_secret'),
            'redirectUri' => url(config('innoclapps.microsoft.redirect_uri')),
            'scopes' => config('innoclapps.microsoft.scopes'),
        ]);
    }

    /**
     * Refresh the token based on a given refresh token
     *
     * @param  string  $type
     * @param  string  $refreshToken
     * @return \League\OAuth2\Client\Token\AccessTokenInterface
     */
    public function refreshToken($type, $refreshToken)
    {
        if (empty($refreshToken)) {
            throw new EmptyRefreshTokenException;
        }

        return $this->createProvider($type)->getAccessToken(new RefreshToken, [
            'refresh_token' => $refreshToken,
        ]);
    }

    /**
     * Get the access token account
     *
     * @param  string  $type
     * @param  string  $email
     * @return \App\Models\OAuthAccount|null
     */
    public function getAccount($type, $email): ?OAuthAccount
    {
        return OAuthAccount::query()->where(['type' => $type, 'email' => $email])->first();
    }

    /**
     * Store account and it's tokens in the database
     *
     * @param  string  $type
     * @param  \League\OAuth2\Client\Token\AccessTokenInterface  $accessToken
     * @param  mixed  $user // HOTASH #
     * @return \App\Models\OAuthAccount
     */
    protected function storeAccount($type, $accessToken, $user)
    {
        $data = [
            'email' => $user->getEmail(),
            'access_token' => $accessToken->getToken(),
            'expires' => $accessToken->getExpires(),
            'oauth_user_id' => $user->getId(),
            'requires_auth' => false,
        ];

        // E.q. for Google, only it's returned on the first connection
        if ($refreshToken = $accessToken->getRefreshToken()) {
            $data['refresh_token'] = $refreshToken;
        }

        if ($this->userId) {
            $data['user_id'] = $this->userId;
        }

        return OAuthAccount::query()->updateOrCreate(['email' => $data['email'], 'type' => $type], $data);
    }
}
