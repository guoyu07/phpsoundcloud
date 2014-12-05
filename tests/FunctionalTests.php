<?php

/**
 * (c) Rob Bast <rob.bast@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Alcohol\Tests;

use Alcohol\SoundCloud;

class FunctionalTests extends \PHPUnit_Framework_TestCase
{
    protected $options = [
        'client_id' => 'myId',
        'secret' => 'mySecret',
        'redirect_uri' => 'http://domain.tld/redirect'
    ];

    /**
     * @test
     * @group functional
     *
     * @return SoundCloud
     */
    public function class_SoundCloud_exists()
    {
        $this->assertTrue(class_exists('Alcohol\SoundCloud'));

        $soundcloud = new SoundCloud($this->options);

        $this->assertInstanceOf('Alcohol\SoundCloud', $soundcloud);

        return $soundcloud;
    }

    /**
     * @test
     * @group functional
     *
     * @expectedException BadMethodCallException
     */
    public function instantiating_class_without_required_options_throws_exception()
    {
        new SoundCloud(array());
    }

    /**
     * @test
     * @group functional
     * @depends class_SoundCloud_exists
     *
     * @param SoundCloud $soundcloud
     */
    public function calling_getTokenAuthUri_returns_valid_uri(SoundCloud $soundcloud)
    {
        $string = $soundcloud->getTokenAuthUri();

        list($uri, $query) = explode('?', $string);

        $this->assertRegExp('#(http|https)://soundcloud.com/connect#', $uri);

        $pairs = explode('&', $query);
        $keys = [];

        foreach ($pairs as $pair) {
            list($keys[], /* $value */) = explode('=', $pair);
        }

        $this->assertTrue(in_array('client_id', $keys));
        $this->assertTrue(in_array('client_secret', $keys));
        $this->assertTrue(in_array('response_type', $keys));
        $this->assertTrue(in_array('scope', $keys));
    }
}
