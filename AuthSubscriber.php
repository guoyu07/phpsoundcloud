<?php

/**
 * (c) Rob Bast <rob.bast@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Alcohol\SoundCloud;

use GuzzleHttp\Collection;
use GuzzleHttp\Event\HasEmitterInterface;
use GuzzleHttp\Event\RequestEvents;
use GuzzleHttp\Event\SubscriberInterface;
use GuzzleHttp\Event\BeforeEvent;

/**
 * Custom authentication listener that handles the "soundcloud" auth type.
 */
class AuthSubscriber implements SubscriberInterface
{
    /** @var Collection */
    private $config;

    /**
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->config = Collection::fromConfig($config, [
            'client_secret' => null,
            'redirect_uri' => null,
            'oauth_token' => null,
        ], ['client_id']);
    }

    /**
     * @param HasEmitterInterface $subject
     * @param array $parameters
     * @return AuthSubscriber
     */
    public static function attach(HasEmitterInterface $subject, array $parameters)
    {
        $subscriber = new self($parameters);
        $emitter = $subject->getEmitter();
        $emitter->attach($subscriber);

        return $subscriber;
    }

    /**
     * @return array
     */
    public function getEvents()
    {
        return ['before' => ['sign', RequestEvents::SIGN_REQUEST]];
    }

    /**
     * @param BeforeEvent $event
     */
    public function sign(BeforeEvent $event)
    {
        $request = $event->getRequest();
        $config = $request->getConfig();

        if (!isset($config['auth']) || 'soundcloud' !== $config['auth']) {
            return;
        }

        $query = $request->getQuery();

        if (null !== ($token = $this->config->get('oauth_token'))) {
            $query->set('oauth_token', $token);
        } else {
            $query->set('client_id', $this->config->get('client_id'));
        }
    }

    /**
     * @return string
     */
    public function getClientId()
    {
        return $this->config->get('client_id');
    }

    /**
     * @param string $client_id
     */
    public function setClientId($client_id)
    {
        $this->config->set('client_id', $client_id);
    }

    /**
     * @return string
     */
    public function getClientSecret()
    {
        return $this->config->get('client_secret');
    }

    /**
     * @param string $client_secret
     */
    public function setClientSecret($client_secret)
    {
        $this->config->set('client_secret', $client_secret);
    }

    /**
     * @return string
     */
    public function getRedirectUri()
    {
        return $this->config->get('redirect_uri');
    }

    /**
     * @param string $redirect_uri
     */
    public function setRedirectUri($redirect_uri)
    {
        $this->config->set('redirect_uri', $redirect_uri);
    }

    /**
     * @return string
     */
    public function getOauthToken()
    {
        return $this->config->get('oauth_token');
    }

    /**
     * @param string $oauth_token
     */
    public function setOauthToken($oauth_token)
    {
        $this->config->set('oauth_token', $oauth_token);
    }
}
