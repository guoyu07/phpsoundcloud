<?php

/**
 * (c) Rob Bast <rob.bast@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Alcohol;

use GuzzleHttp\Message\ResponseInterface;
use GuzzleHttp\Client;

/**
 * Simplistic SoundCloud API wrapper.
 */
class SoundCloud extends Client
{
    /** @var string */
    protected $client_id;

    /** @var string */
    protected $client_secret;

    /** @var string */
    protected $redirect_uri;

    /** @var string */
    protected $token;

    /**
     * @see <a href="http://soundcloud.com/you/apps/new">registering your application</a>
     * @param array $parameters
     *  An array containing the following keys (and their values):
     *   - <tt>client_id</tt>
     *   - <tt>client_secret</tt> [optional]
     *   - <tt>redirect_uri</tt> [optional]
     * @param array $config
     */
    public function __construct(array $parameters, array $config = [])
    {
        if (!isset($parameters['client_id'])) {
            throw new \BadMethodCallException('Missing required option: client_id');
        }

        $this->setClientId($parameters['client_id']);

        if (isset($parameters['client_secret'])) {
            $this->setClientSecret($parameters['client_secret']);
        }

        if (isset($parameters['redirect_uri'])) {
            $this->setRedirectUri($parameters['redirect_uri']);
        }

        $config = array_merge([
            'base_url' => 'https://api.soundcloud.com',
            'defaults' => [
                'headers' => [
                    'Accept' => 'application/json',
                    'User-Agent' => 'github.com/alcohol/phpsoundcloud <rob.bast@gmail.com>',
                ],
            ],
        ], $config);

        parent::__construct($config);
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
        $query = http_build_query(array_merge([
            'client_id' => $this->getClientId(),
            'client_secret' => $this->getClientSecret(),
            'redirect_uri' => $this->getRedirectUri(),
            'response_type' => 'code',
            'scope' => 'non-expiring'
        ], $parameters));

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
        $response = $this->post('/oauth2/token', [
            'body' => [
                'client_id' => $this->getClientId(),
                'client_secret' => $this->getClientSecret(),
                'username' => $username,
                'password' => $password,
                'grant_type' => 'password'
            ]
        ]);

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
        $response = $this->post('/oauth2/token', [
            'body' => [
                'code' => $authorization_code,
                'client_id' => $this->getClientId(),
                'client_secret' => $this->getClientSecret(),
                'redirect_uri' => $this->getRedirectUri(),
                'grant_type' => 'authorization_code'
            ]
        ]);

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
        $response = $this->post('/oauth2/token', [
            'body' => [
                'refresh_token' => $refresh_token,
                'client_id' => $this->getClientId(),
                'client_secret' => $this->getClientSecret(),
                'redirect_uri' => $this->getRedirectUri(),
                'grant_type' => 'refresh_token'
            ]
        ]);

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
        $response = $this->get('/me', ['query' => ['oauth_token' => $this->getToken()]]);

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
        $response = $this->get('/me/activities', ['query' => ['oauth_token' => $this->getToken()]]);

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
        $response = $this->get('/me/connections', ['query' => ['oauth_token' => $this->getToken()]]);

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
        $response = $this->get('/me/activities/tracks/affiliated', ['query' => ['oauth_token' => $this->getToken()]]);

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
        $response = $this->get('/me/tracks', ['query' => array_merge($params, ['oauth_token' => $this->getToken()])]);

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
        $response = $this->get('/me/playlists', ['query' => ['oauth_token' => $this->getToken()]]);

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
        $response = $this->get('/me/favorites', ['query' => ['oauth_token' => $this->getToken()]]);

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
            $options = ['query' => ['oauth_token' => $this->getToken()]];
        } else {
            $options = ['query' => ['client_id' => $this->getClientId()]];
        }

        $response = $this->get('/tracks/'.(int) $track_id, $options);

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
            $options = ['query' => ['oauth_token' => $this->getToken()]];
        } else {
            $options = ['query' => ['client_id' => $this->getClientId()]];
        }

        $response = $this->get('/playlists/'.(int) $playlist_id, $options);

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
        $stream_url = $this->getTrack($track_id)['stream_url'];

        if (null !== $this->getToken()) {
            $options = ['query' => ['oauth_token' => $this->getToken()]];
        } else {
            $options = ['query' => ['client_id' => $this->getClientId()]];
        }

        $options['allow_redirects'] = false;

        $response = $this->get($stream_url, $options);

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
        $response = $this->get('/resolve', ['query' => ['url' => $uri, 'client_id' => $this->getClientId()]]);

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
            $options = ['query' => ['oauth_token' => $this->getToken()]];
        } else {
            $options = ['query' => ['client_id' => $this->getClientId()]];
        }

        $response = $this->get($uri, $options);

        return $this->handleResponse($response);
    }

    /**
     * @param ResponseInterface $response
     * @return mixed
     */
    protected function handleResponse(ResponseInterface $response)
    {
        $content_type = $response->getHeader('Content-type');

        if (false !== stripos($content_type, 'application/json')) {
            return $response->json();
        }

        return $response->getBody();
    }
}
