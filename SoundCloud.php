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

/**
 * Simplistic SoundCloud API wrapper.
 */
class SoundCloud
{
    /** @var string */
    protected $client_id;

    /** @var string */
    protected $client_secret;

    /** @var string */
    protected $redirect_uri;

    /** @var string */
    protected $token;

    /** @var string */
    protected $api_base = 'https://api.soundcloud.com';

    /** @var ClientInterface */
    protected $client;

    /*** @var array */
    protected $headers = ['Accept' => 'application/json'];

    /**
     * Initializes a new instance of <tt>SoundCloud</tt>.
     *
     * @see <a href="http://soundcloud.com/you/apps/new">registering your application</a>
     * @param array $config
     *  An array containing the following keys ( and their values ):
     *
     *   - <tt>client_id</tt>
     *   - <tt>client_secret</tt> [optional]
     *   - <tt>redirect_uri</tt> [optional]
     */
    public function __construct(array $config)
    {
        if (!isset($config['client_id'])) {
            throw new \BadMethodCallException('Missing required option: client_id');
        }

        $this->setClientId($config['client_id']);

        if (isset($config['client_secret'])) {
            $this->setClientSecret($config['client_secret']);
        }

        if (isset($config['redirect_uri'])) {
            $this->setRedirectUri($config['redirect_uri']);
        }
    }

    /**
     * @internal
     * @return string
     */
    public function getApiBase()
    {
        return $this->api_base;
    }

    /**
     * Set the base URI of the SoundCloud API ( defaults to https://api.soundcloud.com ).
     * @param string $uri
     * @return $this
     */
    public function setApiBase($uri)
    {
        $this->api_base = $uri;

        return $this;
    }

    /**
     * @internal
     * @return string
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * Set token to use for authenticated API calls.
     *
     * @param string $token
     * @return $this
     */
    public function setToken($token)
    {
        $this->token = $token;

        return $this;
    }

    /**
     * @internal
     * @return string
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * @internal
     * @param array $headers
     * @return $this
     */
    public function setHeaders(array $headers)
    {
        $this->headers = $headers;

        return $this;
    }

    /**
     * @internal
     * @return string
     */
    public function getClientId()
    {
        return $this->client_id;
    }

    /**
     * @internal
     * @param string $client_id
     * @return $this
     */
    public function setClientId($client_id)
    {
        $this->client_id = $client_id;

        return $this;
    }

    /**
     * @internal
     * @return string
     */
    public function getClientSecret()
    {
        return $this->client_secret;
    }

    /**
     * @internal
     * @param string $client_secret
     * @return $this
     */
    public function setClientSecret($client_secret)
    {
        $this->client_secret = $client_secret;

        return $this;
    }

    /**
     * @internal
     * @return string
     */
    public function getRedirectUri()
    {
        return $this->redirect_uri;
    }

    /**
     * @internal
     * @param string $redirect_uri
     * @return $this
     */
    public function setRedirectUri($redirect_uri)
    {
        $this->redirect_uri = $redirect_uri;

        return $this;
    }

    /**
     * @internal
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
     * @internal
     * @param ClientInterface $client
     * @return $this
     */
    public function setClient(ClientInterface $client)
    {
        $this->client = $client;

        return $this;
    }

    /**
     * Construct URI to authorization endpoint where user can delegate access to your application.
     *
     * @see <a href="https://developers.soundcloud.com/docs/api/reference#connect">api/reference#connect</a>
     * @param array $parameters
     *  Custom parameters can be provided to overrule the default.
     * @param string $connect_uri
     *  Custom connect URI can be provided to overrule the default.
     * @return string
     */
    public function getTokenAuthUri(array $parameters = [], $connect_uri = 'https://soundcloud.com/connect')
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
                $parameters
            )
        );

        return sprintf('%s?%s', $connect_uri, $query);
    }

    /**
     * Retrieve a token using user provided credentials.
     *
     * @see <a href="https://developers.soundcloud.com/docs/api/reference#connect">api/reference#token</a>
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
     * Retrieve a token using the authorization code obtained at your <tt>redirect_uri</tt>.
     *
     * @see <a href="https://developers.soundcloud.com/docs/api/reference#connect">api/reference#token</a>
     * @param string $authorization_code
     * @return mixed
     */
    public function getTokenUsingCode($authorization_code)
    {
        $options = [
            'headers' => $this->getHeaders(),
            'body'    => [
                'code'          => $authorization_code,
                'client_id'     => $this->getClientId(),
                'client_secret' => $this->getClientSecret(),
                'redirect_uri'  => $this->getRedirectUri(),
                'grant_type'    => 'authorization_code'
            ]
        ];

        $response = $this->getClient()->post($this->getApiBase().'/oauth2/token', $options);

        return $this->handleResponse($response);
    }

    /**
     * Retrieve a new token using a <tt>refresh_token</tt>.
     *
     * @see <a href="https://developers.soundcloud.com/docs/api/reference#connect">api/reference#token</a>
     * @param string $refresh_token
     * @return mixed
     */
    public function refreshToken($refresh_token)
    {
        $options = [
            'headers' => $this->getHeaders(),
            'body'    => [
                'refresh_token' => $refresh_token,
                'client_id'     => $this->getClientId(),
                'client_secret' => $this->getClientSecret(),
                'redirect_uri'  => $this->getRedirectUri(),
                'grant_type'    => 'refresh_token'
            ]
        ];

        $response = $this->getClient()->post($this->getApiBase().'/oauth2/token', $options);

        return $this->handleResponse($response);
    }

    /**
     * Return information about the authenticated user.
     *
     * @see <a href="https://developers.soundcloud.com/docs/api/reference#me">api/reference#me</a>
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
     * Return newest activities for the authenticated user.
     *
     * @see <a href="https://developers.soundcloud.com/docs/api/reference#activities">api/reference#activities</a>
     * @return mixed
     * @throws \GuzzleHttp\Exception\ClientException
     */
    public function getActivities()
    {
        $options = [
            'headers' => $this->getHeaders(),
            'query'   => ['oauth_token' => $this->getToken()]
        ];

        $response = $this->getClient()->get($this->getApiBase().'/me/activities', $options);

        return $this->handleResponse($response);
    }

    /**
     * Return external profile connections (twitter, tumblr, facebook, etc) of the authenticated user.
     *
     * @see <a href="https://developers.soundcloud.com/docs/api/reference#connections">api/reference#connections</a>
     * @return mixed
     * @throws \GuzzleHttp\Exception\ClientException
     */
    public function getConnections()
    {
        $options = [
            'headers' => $this->getHeaders(),
            'query'   => ['oauth_token' => $this->getToken()]
        ];

        $response = $this->getClient()->get($this->getApiBase().'/me/connections', $options);

        return $this->handleResponse($response);
    }

    /**
     * Returns recent tracks from users that the authenticated user follows.
     *
     * @see <a href="https://developers.soundcloud.com/docs/api/reference#activities">api/reference#activities</a>
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
     * Returns tracks owned by the authenticated user.
     *
     * @see <a href="https://developers.soundcloud.com/docs/api/reference#me">api/reference#me</a>
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
     * Returns playlists of the authenticated user.
     *
     * @see <a href="https://developers.soundcloud.com/docs/api/reference#me">api/reference#me</a>
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
     * Returns tracks favorited by the authenticated user.
     *
     * @see <a href="https://developers.soundcloud.com/docs/api/reference#me">api/reference#me</a>
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
     * Return details for given track.
     *
     * @see <a href="https://developers.soundcloud.com/docs/api/reference#tracks">api/reference#tracks</a>
     * @param integer $track_id
     * @return mixed
     * @throws \GuzzleHttp\Exception\ClientException
     */
    public function getTrack($track_id)
    {
        if (null !== $this->getToken()) {
            $query = ['oauth_token' => $this->getToken()];
        } else {
            $query = ['client_id' => $this->getClientId()];
        }

        $options = [
            'headers' => $this->getHeaders(),
            'query'   => $query
        ];

        $response = $this->getClient()->get($this->getApiBase().'/tracks/'.(int) $track_id, $options);

        return $this->handleResponse($response);
    }

    /**
     * Return details for given playlist.
     *
     * @see <a href="https://developers.soundcloud.com/docs/api/reference#playlists">api/reference#playlists</a>
     * @param integer $playlist_id
     * @return mixed
     * @throws \GuzzleHttp\Exception\ClientException
     */
    public function getPlaylist($playlist_id)
    {
        if (null !== $this->getToken()) {
            $query = ['oauth_token' => $this->getToken()];
        } else {
            $query = ['client_id' => $this->getClientId()];
        }

        $options = [
            'headers' => $this->getHeaders(),
            'query'   => $query
        ];

        $response = $this->getClient()->get($this->getApiBase().'/playlists/'.(int) $playlist_id, $options);

        return $this->handleResponse($response);
    }

    /**
     * Return the streaming url for given track.
     *
     * @param integer $track_id
     * @return string
     */
    public function getTrackStreamUri($track_id)
    {
        $streamUri = $this->getTrack($track_id)['stream_url'];

        if (null !== $this->getToken()) {
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
     * Resolve and lookup an API resource based on given soundcloud.com URL.
     *
     * @see <a href="https://developers.soundcloud.com/docs/api/reference#resolve">api/reference#resolve</a>
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
     * Retrieve the next page using given URI.
     *
     * @param string $uri
     * @return mixed
     */
    public function getNextPage($uri)
    {
        if (null !== $this->getToken()) {
            $query = ['oauth_token' => $this->getToken()];
        } else {
            $query = ['client_id' => $this->getClientId()];
        }

        $options = [
            'headers' => $this->getHeaders(),
            'query'   => $query
        ];

        $response = $this->getClient()->get($uri, $options);

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

        return $response->getBody();
    }
}
