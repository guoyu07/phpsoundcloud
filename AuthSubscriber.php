<?php

/**
 * (c) Rob Bast <rob.bast@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Alcohol\SoundCloud;

use GuzzleHttp\Event\HasEmitterInterface;
use GuzzleHttp\Event\RequestEvents;
use GuzzleHttp\Event\SubscriberInterface;
use GuzzleHttp\Event\BeforeEvent;

/**
 * Custom authentication listener that handles the "soundcloud" auth type.
 */
class AuthSubscriber implements SubscriberInterface
{
    /** @var string */
    private $client_id;

    /** @var string */
    private $client_secret;

    /** @var string */
    private $redirect_uri;

    /** @var string */
    private $oauth_token;

    /**
     * @param array $parameters
     */
    public function __construct(array $parameters)
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

        if (isset($parameters['oauth_token'])) {
            $this->setOauthToken($parameters['oauth_token']);
        }
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

        if (null !== ($token = $this->getOauthToken())) {
            $query->set('oauth_token', $token);
        } else {
            $query->set('client_id', $this->getClientId());
        }
    }

    /**
     * @return string
     */
    public function getClientId()
    {
        return $this->client_id;
    }

    /**
     * @param string $client_id
     * @return $this
     */
    public function setClientId($client_id)
    {
        $this->client_id = $client_id;

        return $this;
    }

    /**
     * @return string
     */
    public function getClientSecret()
    {
        return $this->client_secret;
    }

    /**
     * @param string $client_secret
     * @return $this
     */
    public function setClientSecret($client_secret)
    {
        $this->client_secret = $client_secret;

        return $this;
    }

    /**
     * @return string
     */
    public function getRedirectUri()
    {
        return $this->redirect_uri;
    }

    /**
     * @param string $redirect_uri
     * @return $this
     */
    public function setRedirectUri($redirect_uri)
    {
        $this->redirect_uri = $redirect_uri;

        return $this;
    }

    /**
     * @return string
     */
    public function getOauthToken()
    {
        return $this->oauth_token;
    }

    /**
     * @param string $oauth_token
     * @return $this
     */
    public function setOauthToken($oauth_token)
    {
        $this->oauth_token = $oauth_token;

        return $this;
    }
}
