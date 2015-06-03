<?php

/*
 * (c) Rob Bast <rob.bast@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Alcohol\SoundCloud;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Message\ResponseInterface;

/**
 * Simplistic SoundCloud API wrapper.
 */
class Client extends GuzzleClient
{
    /** @var AuthSubscriber */
    protected $auth_subscriber;

    /**
     * SoundCloud Client.
     *
     * The parameters array accepts the following options:
     *   - <tt>client_id</tt>
     *   - <tt>client_secret</tt> [optional]
     *   - <tt>redirect_uri</tt> [optional]
     *   - <tt>oauth_token</tt> [optional]
     *
     * @see <a href="http://soundcloud.com/you/apps/new">registering your application</a>
     * @param array $parameters
     * @param array $config
     *  Override the default Guzzle configuration settings.
     */
    public function __construct(array $parameters, array $config = [])
    {
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

        $this->setAuthSubscriber(AuthSubscriber::attach($this, $parameters));
    }

    /**
     * @return AuthSubscriber
     */
    public function getAuthSubscriber()
    {
        return $this->auth_subscriber;
    }

    /**
     * @param AuthSubscriber $auth_subscriber
     */
    protected function setAuthSubscriber(AuthSubscriber $auth_subscriber)
    {
        $this->auth_subscriber = $auth_subscriber;
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
            'client_id' => $this->getAuthSubscriber()->getClientId(),
            'client_secret' => $this->getAuthSubscriber()->getClientSecret(),
            'redirect_uri' => $this->getAuthSubscriber()->getRedirectUri(),
            'response_type' => 'code',
            'scope' => 'non-expiring',
        ], $parameters));

        return sprintf('%s?%s', $connect_uri, $query);
    }

    /**
     * Shortcut for retrieving an oauth token using credentials and setting it on the current SoundCloud instance.
     *
     * @param string $username
     * @param string $password
     * @throws \GuzzleHttp\Exception\ClientException
     */
    public function login($username, $password)
    {
        $response = $this->getTokenUsingCredentials($username, $password);
        $body = $this->handleResponse($response);

        $this->getAuthSubscriber()->setOauthToken($body['access_token']);
    }

    /**
     * Retrieve a token using user provided credentials.
     *
     * @see <a href="https://developers.soundcloud.com/docs/api/reference#connect">api/reference#token</a>
     * @param string $username
     * @param string $password
     * @throws \GuzzleHttp\Exception\ClientException
     * @return mixed
     */
    public function getTokenUsingCredentials($username, $password)
    {
        return $this->getOauthToken([
            'username' => $username,
            'password' => $password,
            'grant_type' => 'password',
        ]);
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
        return $this->getOauthToken([
            'code' => $authorization_code,
            'grant_type' => 'authorization_code',
            'redirect_uri' => $this->getAuthSubscriber()->getRedirectUri(),
        ]);
    }

    /**
     * Retrieve a new token using a <tt>refresh_token</tt>.
     *
     * @see <a href="https://developers.soundcloud.com/docs/api/reference#connect">api/reference#token</a>
     * @param string $refresh_token
     * @return mixed
     */
    public function getTokenUsingRefreshToken($refresh_token)
    {
        return $this->getOauthToken([
            'refresh_token' => $refresh_token,
            'grant_type' => 'refresh_token',
            'redirect_uri' => $this->getAuthSubscriber()->getRedirectUri(),
        ]);
    }

    /**
     * @param array $body
     * @return mixed
     */
    protected function getOauthToken($body)
    {
        $response = $this->post('/oauth2/token', [
            'body' => [
                'client_id' => $this->getAuthSubscriber()->getClientId(),
                'client_secret' => $this->getAuthSubscriber()->getClientSecret(),
            ] + $body,
        ]);

        return $this->handleResponse($response);
    }

    /**
     * Return information about the authenticated user.
     *
     * @see <a href="https://developers.soundcloud.com/docs/api/reference#me">api/reference#me</a>
     * @throws \GuzzleHttp\Exception\ClientException
     * @return mixed
     */
    public function getMe()
    {
        $response = $this->get('/me', ['auth' => 'soundcloud']);

        return $this->handleResponse($response);
    }

    /**
     * Return newest activities for the authenticated user.
     *
     * @see <a href="https://developers.soundcloud.com/docs/api/reference#activities">api/reference#activities</a>
     * @throws \GuzzleHttp\Exception\ClientException
     * @return mixed
     */
    public function getActivities()
    {
        $response = $this->get('/me/activities', ['auth' => 'soundcloud']);

        return $this->handleResponse($response);
    }

    /**
     * Return external profile connections (twitter, tumblr, facebook, etc) of the authenticated user.
     *
     * @see <a href="https://developers.soundcloud.com/docs/api/reference#connections">api/reference#connections</a>
     * @throws \GuzzleHttp\Exception\ClientException
     * @return mixed
     */
    public function getConnections()
    {
        $response = $this->get('/me/connections', ['auth' => 'soundcloud']);

        return $this->handleResponse($response);
    }

    /**
     * Returns recent tracks from users that the authenticated user follows.
     *
     * @see <a href="https://developers.soundcloud.com/docs/api/reference#activities">api/reference#activities</a>
     * @throws \GuzzleHttp\Exception\ClientException
     * @return mixed
     */
    public function getStream()
    {
        $response = $this->get('/me/activities/tracks/affiliated', ['auth' => 'soundcloud']);

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
        $response = $this->get('/me/tracks', ['query' => $params, 'auth' => 'soundcloud']);

        return $this->handleResponse($response);
    }

    /**
     * Returns playlists of the authenticated user.
     *
     * @see <a href="https://developers.soundcloud.com/docs/api/reference#me">api/reference#me</a>
     * @throws \GuzzleHttp\Exception\ClientException
     * @return mixed
     */
    public function getPlaylists()
    {
        $response = $this->get('/me/playlists', ['auth' => 'soundcloud']);

        return $this->handleResponse($response);
    }

    /**
     * Returns tracks favorited by the authenticated user.
     *
     * @see <a href="https://developers.soundcloud.com/docs/api/reference#me">api/reference#me</a>
     * @throws \GuzzleHttp\Exception\ClientException
     * @return mixed
     */
    public function getFavorites()
    {
        $response = $this->get('/me/favorites', ['auth' => 'soundcloud']);

        return $this->handleResponse($response);
    }

    /**
     * Return details for given track.
     *
     * @see <a href="https://developers.soundcloud.com/docs/api/reference#tracks">api/reference#tracks</a>
     * @param int $track_id
     * @throws \GuzzleHttp\Exception\ClientException
     * @return mixed
     */
    public function getTrack($track_id)
    {
        $response = $this->get('/tracks/' . (int) $track_id, ['auth' => 'soundcloud']);

        return $this->handleResponse($response);
    }

    /**
     * Return details for given playlist.
     *
     * @see <a href="https://developers.soundcloud.com/docs/api/reference#playlists">api/reference#playlists</a>
     * @param int $playlist_id
     * @throws \GuzzleHttp\Exception\ClientException
     * @return mixed
     */
    public function getPlaylist($playlist_id)
    {
        $response = $this->get('/playlists/' . (int) $playlist_id, ['auth' => 'soundcloud']);

        return $this->handleResponse($response);
    }

    /**
     * Return the streaming url for given track.
     *
     * @param int $track_id
     * @return string
     */
    public function getTrackStreamUri($track_id)
    {
        $stream_url = $this->getTrack($track_id)['stream_url'];

        $response = $this->get($stream_url, ['auth' => 'soundcloud', 'allow_redirects' => false]);

        return $response->getHeader('Location');
    }

    /**
     * Resolve and lookup an API resource based on given soundcloud.com URL.
     *
     * @see <a href="https://developers.soundcloud.com/docs/api/reference#resolve">api/reference#resolve</a>
     * @param string $uri
     * @throws \GuzzleHttp\Exception\ClientException
     * @return string
     */
    public function resolveUri($uri)
    {
        $response = $this->get('/resolve', ['query' => ['url' => $uri], 'auth' => 'soundcloud']);

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
        $response = $this->get($uri, ['auth' => 'soundcloud']);

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
