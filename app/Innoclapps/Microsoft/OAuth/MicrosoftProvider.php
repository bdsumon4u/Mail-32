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

namespace App\Innoclapps\Microsoft\OAuth;

use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Provider\GenericProvider;
use League\OAuth2\Client\Token\AccessToken;
use Psr\Http\Message\ResponseInterface;

class MicrosoftProvider extends GenericProvider
{
    /**
     * @param  array  $options
     * @param  array  $collaborators
     */
    public function __construct(array $options = [], array $collaborators = [])
    {
        parent::__construct(array_merge($options, [
            'urlAuthorize' => $this->getBaseAuthorizationUrl(),
            'urlAccessToken' => $this->getBaseAccessTokenUrl([]),
            'urlResourceOwnerDetails' => 'https://graph.microsoft.com/v1.0/me',
        ]), $collaborators);
    }

    /**
     * Returns the base URL for authorizing a client.
     *
     * @return string
     */
    public function getBaseAuthorizationUrl()
    {
        $config = config('innoclapps.microsoft');

        return collect([
            $config['login_url_base'],
            $config['tenant_id'],
            $config['oauth2_path'],
        ])->map(fn ($part) => trim($part, '/'))->implode('/').'/authorize';
    }

    /**
     * Returns the base URL for requesting an access token.
     *
     * @param  array  $params
     * @return string
     */
    public function getBaseAccessTokenUrl(array $params)
    {
        $config = config('innoclapps.microsoft');

        return collect([
            $config['login_url_base'],
            $config['tenant_id'],
            $config['oauth2_path'],
        ])->map(fn ($part) => trim($part, '/'))->implode('/').'/token';
    }

    /**
     * Generate a user object from a successful user details request.
     *
     * @param  array  $response
     * @param  \League\OAuth2\Client\Token\AccessToken  $token
     * @return \App\Innoclapps\Microsoft\OAuth\MicrosoftResourceOwner
     */
    protected function createResourceOwner(array $response, AccessToken $token)
    {
        return new MicrosoftResourceOwner($response);
    }

    /**
     * {@inheritdoc}
     *
     * @see https://docs.microsoft.com/en-us/graph/auth-v2-user#authorization-request
     */
    protected function getScopeSeparator()
    {
        return ' ';
    }

    /**
     * Checks a provider response for errors.
     *
     * @param  \Psr\Http\Message\ResponseInterface  $response
     * @param  array|string  $data Parsed response data
     * @return void
     *
     * @throws \League\OAuth2\Client\Provider\Exception\IdentityProviderException
     */
    protected function checkResponse(ResponseInterface $response, $data)
    {
        if (isset($data['error'])) { // HOTASH # ERROR
            throw new IdentityProviderException(
                (isset($data['error']['message']) ? $data['error']['message'] : $response->getReasonPhrase()),
                $response->getStatusCode(),
                $response // @phpstan-ignore-line
            );
        }
    }
}
