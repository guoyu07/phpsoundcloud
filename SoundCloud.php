<?php

/**
 * (c) Rob Bast <rob.bast@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Alcohol;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Message\ResponseInterface;
use GuzzleHttp\Client;

class SoundCloud
{
    /** @var string */
    protected $clientId;

    /** @var string */
    protected $clientSecret;

    /** @var string */
    protected $redirectUri;

    /** @var string */
    protected $token;

    /** @var string */
    protected $apiBase = 'https://api.soundcloud.com';

    /** @var ClientInterface */
    protected $client;

    /*** @var array */
    protected $headers = ['Accept' => 'application/json'];

    /**
     * @param array $options
     */
    public function __construct(array $options)
    {
        if (!isset($options['client_id'])) {
            throw new \BadMethodCallException('Missing required option: client_id');
        }

        $this->setClientId($options['client_id']);

        if (isset($options['client_secret'])) {
            $this->setClientSecret($options['client_secret']);
        }

        if (isset($options['redirect_uri'])) {
            $this->setRedirectUri($options['redirect_uri']);
        }
    }

    /**
     * @return string
     */
    public function getApiBase()
    {
        return $this->apiBase;
    }

    /**
     * @param string $uri
     * @return SoundCloud
     */
    public function setApiBase($uri)
    {
        $this->apiBase = $uri;

        return $this;
    }

    /**
     * @return string
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * @param string $token
     * @return SoundCloud
     */
    public function setToken($token)
    {
        $this->token = $token;

        return $this;
    }

    /**
     * @return string
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * @param array $headers
     * @return SoundCloud
     */
    public function setHeaders(array $headers)
    {
        $this->headers = $headers;

        return $this;
    }

    /**
     * @return string
     */
    public function getClientId()
    {
        return $this->clientId;
    }

    /**
     * @param string $clientId
     * @return SoundCloud
     */
    public function setClientId($clientId)
    {
        $this->clientId = $clientId;

        return $this;
    }

    /**
     * @return string
     */
    public function getClientSecret()
    {
        return $this->clientSecret;
    }

    /**
     * @param string $clientSecret
     * @return SoundCloud
     */
    public function setClientSecret($clientSecret)
    {
        $this->clientSecret = $clientSecret;

        return $this;
    }

    /**
     * @return string
     */
    public function getRedirectUri()
    {
        return $this->redirectUri;
    }

    /**
     * @param string $redirectUri
     * @return SoundCloud
     */
    public function setRedirectUri($redirectUri)
    {
        $this->redirectUri = $redirectUri;

        return $this;
    }

    /**
     * @return ClientInterface
     */
    public function getClient()
    {
        if (is_null($this->client)) {
            $this->client = new Client();
        }

        return $this->client;
    }

    /**
     * @param ClientInterface $client
     * @return SoundCloud
     */
    public function setClient(ClientInterface $client)
    {
        $this->client = $client;

        return $this;
    }

    /**
     * @return SoundCloud
     */
    public function returnJson()
    {
        $this->headers = array_merge($this->headers, ['Accept' => 'application/json']);

        return $this;
    }

    /**
     * @return SoundCloud
     */
    public function returnXml()
    {
        $this->headers = array_merge($this->headers, ['Accept' => 'application/xml']);

        return $this;
    }

    /**
     * @param array  $options
     * @param string $connectUri
     * @return string
     */
    public function getTokenAuthUri(array $options = [], $connectUri = 'https://soundcloud.com/connect')
    {
        $query = http_build_query(
            array_merge(
                [
                    'client_id'     => $this->getClientId(),
                    'client_secret' => $this->getClientSecret(),
                    'redirect_uri'  => $this->getRedirectUri(),
                    'response_type' => 'code',
                    'scope'         => 'non-expiring'
                ],
                $options
            )
        );

        return sprintf('%s?%s', $connectUri, $query);
    }

    /**
     * @param string $username
     * @param string $password
     * @return mixed
     * @throws \GuzzleHttp\Exception\ClientException
     */
    public function getTokenUsingCredentials($username, $password)
    {
        $options = [
            'headers' => $this->getHeaders(),
            'body'    => [
                'client_id'     => $this->getClientId(),
                'client_secret' => $this->getClientSecret(),
                'username'      => $username,
                'password'      => $password,
                'grant_type'    => 'password'
            ]
        ];

        $response = $this->getClient()->post($this->getApiBase().'/oauth2/token', $options);

        return $this->handleResponse($response);
    }

    /**
     * @param string $code
     * @param array  $body
     * @return mixed
     */
    public function getTokenUsingCode($code, array $body = [])
    {
        $options = [
            'headers' => $this->getHeaders(),
            'body'    => [
                'code'          => $code,
                'client_id'     => $this->getClientId(),
                'client_secret' => $this->getClientSecret(),
                'redirect_uri'  => $this->getRedirectUri(),
                'grant_type'    => 'authorization_code'
            ] + $body
        ];

        $response = $this->getClient()->post($this->getApiBase().'/oauth2/token', $options);

        return $this->handleResponse($response);
    }

    /**
     * @param string $refreshToken
     * @param array  $body
     * @return mixed
     */
    public function refreshToken($refreshToken, array $body = [])
    {
        $options = [
            'headers' => $this->getHeaders(),
            'body'    => [
                'refresh_token' => $refreshToken,
                'client_id'     => $this->getClientId(),
                'client_secret' => $this->getClientSecret(),
                'redirect_uri'  => $this->getRedirectUri(),
                'grant_type'    => 'refresh_token'
            ] + $body
        ];

        $response = $this->getClient()->post($this->getApiBase().'/oauth2/token', $options);

        return $this->handleResponse($response);
    }

    /**
     * @return mixed
     * @throws \GuzzleHttp\Exception\ClientException
     */
    public function getMe()
    {
        $options = [
            'headers' => $this->getHeaders(),
            'query'   => ['oauth_token' => $this->getToken()]
        ];

        $response = $this->getClient()->get($this->getApiBase().'/me', $options);

        return $this->handleResponse($response);
    }

    /**
     * @return mixed
     * @throws \GuzzleHttp\Exception\ClientException
     */
    public function getStream()
    {
        $options = [
            'headers' => $this->getHeaders(),
            'query'   => ['oauth_token' => $this->getToken()]
        ];

        $response = $this->getClient()->get($this->getApiBase().'/me/activities/tracks/affiliated', $options);

        return $this->handleResponse($response);
    }

    /**
     * @param array $params
     * @return mixed
     */
    public function getTracks(array $params = [])
    {
        $options = [
            'headers' => $this->getHeaders(),
            'query'   => array_merge($params, ['oauth_token' => $this->getToken()])
        ];

        $response = $this->getClient()->get($this->getApiBase().'/me/tracks', $options);

        return $this->handleResponse($response);
    }

    /**
     * @return mixed
     * @throws \GuzzleHttp\Exception\ClientException
     */
    public function getPlaylists()
    {
        $options = [
            'headers' => $this->getHeaders(),
            'query'   => ['oauth_token' => $this->getToken()]
        ];

        $response = $this->getClient()->get($this->getApiBase().'/me/playlists', $options);

        return $this->handleResponse($response);
    }

    /**
     * @return mixed
     * @throws \GuzzleHttp\Exception\ClientException
     */
    public function getFavorites()
    {
        $options = [
            'headers' => $this->getHeaders(),
            'query'   => ['oauth_token' => $this->getToken()]
        ];

        $response = $this->getClient()->get($this->getApiBase().'/me/favorites', $options);

        return $this->handleResponse($response);
    }

    /**
     * @param integer $trackId
     * @return mixed
     * @throws \GuzzleHttp\Exception\ClientException
     */
    public function getTrack($trackId)
    {
        if (!is_null($this->getToken())) {
            $query = ['oauth_token' => $this->getToken()];
        } else {
            $query = ['client_id' => $this->getClientId()];
        }

        $options = [
            'headers' => $this->getHeaders(),
            'query'   => $query
        ];

        $response = $this->getClient()->get($this->getApiBase().'/tracks/'.(int) $trackId, $options);

        return $this->handleResponse($response);
    }

    /**
     * @param integer $playlistId
     * @return mixed
     * @throws \GuzzleHttp\Exception\ClientException
     */
    public function getPlaylist($playlistId)
    {
        if (!is_null($this->getToken())) {
            $query = ['oauth_token' => $this->getToken()];
        } else {
            $query = ['client_id' => $this->getClientId()];
        }

        $options = [
            'headers' => $this->getHeaders(),
            'query'   => $query
        ];

        $response = $this->getClient()->get($this->getApiBase().'/playlists/'.(int) $playlistId, $options);

        return $this->handleResponse($response);
    }

    /**
     * @param integer $trackId
     * @return string
     */
    public function getTrackStreamUri($trackId)
    {
        $headers = $this->getHeaders();
        $this->returnJson();
        $streamUri = $this->getTrack($trackId)['stream_url'];
        $this->setHeaders($headers);

        if (!is_null($this->getToken())) {
            $query = ['oauth_token' => $this->getToken()];
        } else {
            $query = ['client_id' => $this->getClientId()];
        }

        $options = [
            'headers'         => $this->getHeaders(),
            'query'           => $query,
            'allow_redirects' => false
        ];

        $response = $this->getClient()->get($streamUri, $options);

        return $response->getHeader('Location');
    }

    /**
     * @param string $uri
     * @return string
     * @throws \GuzzleHttp\Exception\ClientException
     */
    public function resolveUri($uri)
    {
        $options = [
            'headers' => $this->getHeaders(),
            'query'   => ['url' => $uri, 'client_id' => $this->getClientId()]
        ];

        $response = $this->getClient()->get($this->getApiBase().'/resolve', $options);

        return $response->getEffectiveUrl();
    }

    /**
     * @param $nextPageHref
     * @return mixed
     */
    public function getNextPage($nextPageHref)
    {
        $options = [
            'headers' => $this->getHeaders(),
            'query'   => ['client_id' => $this->getClientId()]
        ];

        $response = $this->getClient()->get($nextPageHref, $options);

        return $this->handleResponse($response);
    }

    /**
     * @param ResponseInterface $response
     * @return mixed
     */
    protected function handleResponse(ResponseInterface $response)
    {
        $contentType = $response->getHeader('Content-type');

        if (false !== stripos($contentType, 'application/json')) {
            return $response->json();
        }

        if (false !== stripos($contentType, 'application/xml')) {
            return $response->xml();
        }

        return $response->getBody();
    }
}
