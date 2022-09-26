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

namespace App\Innoclapps\Microsoft;

use App\Innoclapps\Microsoft\Services\Batch\BatchRequests;
use App\Innoclapps\Microsoft\Services\Batch\Request as BatchRequest;
use App\Innoclapps\OAuth\AccessTokenProvider;
use App\Innoclapps\OAuth\OAuthManager;
use Illuminate\Contracts\Support\Arrayable;
use Microsoft\Graph\Graph;
use Microsoft\Graph\Http\GraphCollectionRequest;

class Client
{
    protected ?Graph $client;

    protected static string $email;

    protected static string $baseUrl = 'https://graph.microsoft.com/';

    protected static string $apiVersion = 'v1.0';

    /**
     * Provide a connector for the access token
     *
     * @param  string|\App\Innoclapps\OAuth\AccessTokenProvider  $connector
     * @return static
     */
    public function connectUsing(string|AccessTokenProvider $connector): static
    {
        static::$email = is_string($connector) ? $connector : $connector->getEmail();

        // Reset the client so the next time can be retrieved with the new connector
        $this->client = null;

        return $this;
    }

    /**
     * Set the Microsoft Graph API Version
     *
     * @param  string  $version
     * @return static
     */
    public function setApiVersion(string $version)
    {
        static::$apiVersion = $version;

        return $this;
    }

    /**
     * Get the Microsoft Graph API Version
     *
     * @return string
     */
    public function getApiVersion(): string
    {
        return static::$apiVersion;
    }

    /**
     * Set the Microsoft Graph API Base URL
     *
     * @param  string  $url
     * @return static
     */
    public function setBaseUrl(string $url): static
    {
        static::$baseUrl = $url;

        return $this;
    }

    /**
     * The function can be used to iterate over a collection request
     * to get all the results from the collection via all the pages
     *
     * @param  \Microsoft\Graph\Http\GraphCollectionRequest  $requestIterator
     * @return array
     */
    public function iterateCollectionRequest(GraphCollectionRequest $requestIterator): array
    {
        $entities = [];

        while (! $requestIterator->isEnd()) {
            $data = $requestIterator->getPage();

            // https://github.com/microsoftgraph/msgraph-sdk-php/issues/46
            if (is_array($data)) {
                $entities = array_merge($entities, $data);
            }
        }

        return $entities;
    }

    /**
     * Create request
     *
     * @param  string  $requestType Request type ('get', 'post', 'patch', 'put', 'delete')
     * @param  string  $endpoint Graph endpoint
     * @return \Microsoft\Graph\Http\GraphRequest
     */
    public function createRequest($requestType, $endpoint)
    {
        return $this->getClient()->createRequest($requestType, $endpoint);
    }

    /**
     * Create POST request
     *
     * @param  string  $endpoint
     * @param  mixed  $body
     * @return \Microsoft\Graph\Http\GraphRequest
     */
    public function createPostRequest($endpoint, $body = null)
    {
        $request = $this->createRequest('POST', $endpoint);

        if ($body) {
            $request->attachBody($body instanceof Arrayable ? $body->toArray() : $body);
        }

        return $request;
    }

    /**
     * Create PATCH request
     *
     * @param  string  $endpoint
     * @param  mixed  $body
     * @return \Microsoft\Graph\Http\GraphRequest
     */
    public function createPatchRequest($endpoint, $body = null)
    {
        $request = $this->createRequest('PATCH', $endpoint);

        if ($body) {
            $request->attachBody($body instanceof Arrayable ? $body->toArray() : $body);
        }

        return $request;
    }

    /**
     * Create PUT request
     *
     * @param  string  $endpoint
     * @param  mixed  $body
     * @return \Microsoft\Graph\Http\GraphRequest
     */
    public function createPutRequest($endpoint, $body = null)
    {
        $request = $this->createRequest('PUT', $endpoint);

        if ($body) {
            $request->attachBody($body instanceof Arrayable ? $body->toArray() : $body);
        }

        return $request;
    }

    /**
     * Create GET request
     *
     * @param  string  $endpoint
     * @return \Microsoft\Graph\Http\GraphRequest
     */
    public function createGetRequest($endpoint)
    {
        return $this->createRequest('GET', $endpoint);
    }

    /**
     * Create DELETE request
     *
     * @param  string  $endpoint
     * @return \Microsoft\Graph\Http\GraphRequest
     */
    public function createDeleteRequest($endpoint)
    {
        return $this->createRequest('DELETE', $endpoint);
    }

    /**
     * Create collection request
     *
     * @param  string  $requestType Request type ('get', 'post', 'patch', 'put', 'delete')
     * @param  string  $endpoint Graph endpoint
     * @return \Microsoft\Graph\Http\GraphCollectionRequest
     */
    public function createCollectionRequest($requestType, $endpoint)
    {
        return $this->getClient()->createCollectionRequest($requestType, $endpoint);
    }

    /**
     * Create collection GET request
     *
     * @param  string  $endpoint
     * @return \Microsoft\Graph\Http\GraphCollectionRequest
     */
    public function createCollectionGetRequest($endpoint)
    {
        return $this->createCollectionRequest('GET', $endpoint);
    }

    /**
     * Create batch request
     *
     * @param  \App\Innoclapps\Microsoft\Services\Batch\BatchRequests  $requests
     * @return \App\Innoclapps\Microsoft\Services\Batch\Request
     */
    public function createBatchRequest(BatchRequests $requests)
    {
        $request = $this->createCollectionRequest('POST', '/$batch');

        return new BatchRequest($request, $requests);
    }

    /**
     * Get the client graph class
     *
     * @return \Microsoft\Graph\Graph
     */
    public function getClient(): Graph
    {
        if ($this->client) {
            // In case the version is dynamically changed
            // Update in the current Graph instance too
            return $this->client->setApiVersion(self::$apiVersion);
        }

        return $this->client = tap(new Graph, function ($client) {
            $accessToken = (new OAuthManager)->retrieveAccessToken('microsoft', static::$email);

            $client->setAccessToken($accessToken)
                ->setBaseUrl(self::$baseUrl)
                ->setApiVersion(self::$apiVersion);

            // HOTASH # ERROR
            if ($preferredTimezone = config('microsoft.preferTimezone')) {
                // ERROR: $client->addHeaders(['Prefer' => 'outlook.timezone="' . $preferredTimezone . '"']);
            }
        });
    }
}
