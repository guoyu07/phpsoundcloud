<?php

/**
 * (c) Rob Bast <rob.bast@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Alcohol\Tests;

use Alcohol\SoundCloud;
use GuzzleHttp\Subscriber\Mock;
use GuzzleHttp\Message\Response;
use GuzzleHttp\Stream\Stream;

class SoundCloudTest extends \PHPUnit_Framework_TestCase
{
    /** @var SoundCloud */
    protected $soundcloud;

    /** @var Mock */
    protected $mock;

    public function setUp()
    {
        $options = [
            'client_id' => getenv('client_id') ?: 'myId',
            'client_secret' => getenv('client_secret') ?: 'mySecret',
            'redirect_uri' => getenv('redirect_uri') ?: 'http://domain.tld/redirect'
        ];

        $this->soundcloud = new SoundCloud($options);

        if (!getenv('client_id')) {
            $this->mock = new Mock();
            $this->soundcloud->getClient()->getEmitter()->attach($this->mock);
        }
    }

    /**
     * @group functional
     * @testdox Instantiating the SoundCloud class without required options throws a BadMethodCallException.
     *
     * @expectedException \BadMethodCallException
     */
    public function testSoundCloudConstructor()
    {
        new SoundCloud(array());
    }

    /**
     * @group functional
     * @testdox Calling getTokenAuthUri returns a valid uri.
     */
    public function testGetTokenAuthUri()
    {
        $string = $this->soundcloud->getTokenAuthUri();

        list($uri, $query) = explode('?', $string);

        $this->assertRegExp('#(http|https)://soundcloud.com/connect#', $uri);

        $pairs = explode('&', $query);
        $keys = [];

        foreach ($pairs as $pair) {
            list($keys[], /* $value */) = explode('=', $pair);
        }

        $this->assertTrue(in_array('client_id', $keys));
        $this->assertTrue(in_array('client_secret', $keys));
        $this->assertTrue(in_array('redirect_uri', $keys));
        $this->assertTrue(in_array('response_type', $keys));
        $this->assertTrue(in_array('scope', $keys));
    }

    /**
     * @testdox Calling getTokenUsingCredentials with invalid credentials throws a ClientException.
     * @group integration
     * @expectedException \GuzzleHttp\Exception\ClientException
     */
    public function testGetTokenUsingCredentialsThrowsException()
    {
        $username = 'myUsername';
        $password = 'myPassword';

        if (isset($this->mock)) {
            $this->mock->addResponse(new Response(401));
        }

        $this->soundcloud->getTokenUsingCredentials($username, $password);
    }

    /**
     * @testdox Calling getTokenUsingCredentials with valid credentials should return a token.
     * @group integration
     */
    public function testGetTokenUsingCredentials()
    {
        $username = getenv('soundcloud_username');
        $password = getenv('soundcloud_password');

        if (isset($this->mock)) {
            $this->mock->addResponse(
                new Response(
                    200,
                    ['Content-type' => 'application/json'],
                    Stream::factory(json_encode([
                        'access_token' => 'dummy-access-token',
                        'expires_in' => 300,
                        'scope' => '*',
                        'refresh_token' => 'dummy-refresh-token'
                    ]))
                )
            );
        }

        $result = $this->soundcloud->getTokenUsingCredentials($username, $password);

        $this->assertTrue(array_key_exists('access_token', $result));
        $this->assertTrue(array_key_exists('expires_in', $result));
        $this->assertTrue(array_key_exists('scope', $result));
        $this->assertTrue(array_key_exists('refresh_token', $result));

        return $result['access_token'];
    }

    /**
     * @testdox Calling getStream with a invalid token should throw a ClientException.
     * @group integration
     * @expectedException \GuzzleHttp\Exception\ClientException
     */
    public function testGetStreamThrowsException()
    {
        if (isset($this->mock)) {
            $this->mock->addResponse(new Response(401));
        }

        $this->soundcloud->setToken('invalid-token');
        $this->soundcloud->getStream();
    }

    /**
     * @testdox Calling getStream with a valid token should return a stream array.
     * @group integration
     * @depends testGetTokenUsingCredentials
     *
     * @param string $token
     */
    public function testGetStreamWithToken($token)
    {
        if (isset($this->mock)) {
            $this->mock->addResponse(
                new Response(
                    200,
                    ['Content-type' => 'application/json'],
                    Stream::factory(json_encode([
                        'collection' => [],
                        'next_href' => 'http://soundcloud.com/next',
                        'future_href' => 'http://soundcloud.com/future'
                    ]))
                )
            );
        }

        $this->soundcloud->setToken($token);
        $result = $this->soundcloud->getStream();

        $this->assertTrue(array_key_exists('collection', $result));
        $this->assertTrue(array_key_exists('next_href', $result));
        $this->assertTrue(array_key_exists('future_href', $result));
    }

    /**
     * @testdox Calling getFavorites with a invalid token should throw a ClientException.
     * @group integration
     * @expectedException \GuzzleHttp\Exception\ClientException
     */
    public function testGetFavoritesThrowsException()
    {
        if (isset($this->mock)) {
            $this->mock->addResponse(new Response(401));
        }

        $this->soundcloud->setToken('invalid-token');
        $this->soundcloud->getFavorites();
    }

    /**
     * @testdox Calling getFavorites with a valid token should return a favorites array.
     * @group integration
     * @depends testGetTokenUsingCredentials
     *
     * @param string $token
     */
    public function testGetFavoritesWithToken($token)
    {
        if (isset($this->mock)) {
            $this->mock->addResponse(
                new Response(200, ['Content-type' => 'application/json'], Stream::factory(json_encode([])))
            );
        }

        $this->soundcloud->setToken($token);
        $result = $this->soundcloud->getFavorites();
        $this->assertTrue(is_array($result));
    }

    /**
     * @testdox Calling getPlaylists with a invalid token should throw a ClientException.
     * @group integration
     * @expectedException \GuzzleHttp\Exception\ClientException
     */
    public function testGetPlaylistsThrowsException()
    {
        if (isset($this->mock)) {
            $this->mock->addResponse(new Response(401));
        }

        $this->soundcloud->setToken('invalid-token');
        $this->soundcloud->getPlaylists();
    }

    /**
     * @testdox Calling getPlaylists with a valid token should return a playlists array.
     * @group integration
     * @depends testGetTokenUsingCredentials
     *
     * @param string $token
     */
    public function testGetPlaylistsWithToken($token)
    {
        if (isset($this->mock)) {
            $this->mock->addResponse(
                new Response(
                    200,
                    ['Content-type' => 'application/json'],
                    Stream::factory(json_encode([
                        ['kind' => 'playlist']
                    ]))
                )
            );
        }

        $this->soundcloud->setToken($token);
        $result = $this->soundcloud->getPlaylists();
        $this->assertTrue(is_array($result));
        $first = current($result);
        $this->assertEquals('playlist', $first['kind']);
    }

    /**
     * @testdox Calling getPlaylist anonymously returns an array with playlist details.
     * @group integration
     */
    public function testGetPlaylistAnonymous()
    {
        if (isset($this->mock)) {
            $this->mock->addResponse(
                new Response(
                    200,
                    ['Content-type' => 'application/json'],
                    Stream::factory(json_encode([
                        'kind' => 'playlist'
                    ]))
                )
            );
        }

        $this->soundcloud->setToken(null);
        $playlistId = getenv('playlist_id');
        $result = $this->soundcloud->getPlaylist($playlistId);
        $this->assertTrue(is_array($result));
        $this->assertTrue(array_key_exists('kind', $result));
        $this->assertEquals('playlist', $result['kind']);
    }

    /**
     * @testdox Calling getTrack anonymously returns an array with track details.
     * @group integration
     */
    public function testGetTrackAnonymous()
    {
        if (isset($this->mock)) {
            $this->mock->addResponse(
                new Response(
                    200,
                    ['Content-type' => 'application/json'],
                    Stream::factory(json_encode([
                        'kind' => 'track'
                    ]))
                )
            );
        }

        $this->soundcloud->setToken(null);
        $trackId = getenv('track_id');
        $result = $this->soundcloud->getTrack($trackId);
        $this->assertTrue(is_array($result));
        $this->assertTrue(array_key_exists('kind', $result));
        $this->assertEquals('track', $result['kind']);
    }

    /**
     * @testdox Calling getTrackStreamUri anonymously returns streaming uri.
     * @group integration
     */
    public function testGetTrackStreamUriAnonymous()
    {
        if (isset($this->mock)) {
            $this->mock->addMultiple([
                new Response(
                    200,
                    ['Content-type' => 'application/json'],
                    Stream::factory(json_encode([
                        'kind' => 'track',
                        'stream_url' => 'http://soundcloud/placeholder'
                    ]))
                ),
                new Response(
                    302,
                    ['Location' => 'http://soundcloud.com/streamuri']
                )
            ]);
        }

        $this->soundcloud->setToken(null);
        $trackId = getenv('track_id');
        $result = $this->soundcloud->getTrackStreamUri($trackId);
        $this->assertInternalType('string', $result);

        if (isset($this->mock)) {
            $this->assertEquals('http://soundcloud.com/streamuri', $result);
        }
    }

    /**
     * @testdox Calling resolveUri anonymously returns correct resolved target uri.
     * @group integration
     */
    public function testResolveUriAnonymous()
    {
        $this->soundcloud->setToken(null);
        $playlistId = getenv('playlist_id') ?: '1';
        $playlistUri = getenv('playlist_uri') ?: 'https://soundcloud.com/myUsername/sets/myPlaylist';

        if (isset($this->mock)) {
            $this->mock->addMultiple([
                new Response(
                    302,
                    ['Location' => 'http://soundcloud.com/resolved/' . $playlistId]
                ),
                new Response(200)
            ]);
        }

        $result = $this->soundcloud->resolveUri($playlistUri);
        $this->assertEquals(1, preg_match('/(\d+)/', $result, $matches));
        $this->assertEquals($playlistId, $matches[1]);

        if (isset($this->mock)) {
            $this->assertEquals('http://soundcloud.com/resolved/' . $playlistId, $result);
        }
    }
}
