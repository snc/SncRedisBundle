<?php

/*
 * This file is part of the SncRedisBundle package.
 *
 * (c) Henrik Westphal <henrik.westphal@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Snc\RedisBundle\SessionStorage;

use Symfony\Component\HttpFoundation\SessionStorage\NativeSessionStorage;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Predis\Client;

/**
 * Redis based session storage
 *
 * @author  Justin Rainbow <justin.rainbow@gmail.com>
 * @author  Jordi Boggiano <j.boggiano@seld.be>
 */
class RedisSessionStorage extends NativeSessionStorage
{
    /**
     * Instance of Client
     *
     * @var Client
     */
    protected $db;

    /**
     * Redis session storage constructor
     *
     * @param Client $db      Redis database connection
     * @param array  $options Session options
     * @param string $prefix  Prefix to use when writing session data
     */
    public function __construct(Client $db, $options = array(), $prefix = 'session')
    {
        $this->db = $db;

        $options['prefix'] = $prefix;

        parent::__construct($options);
    }

    /**
     * Starts the session and registers session save handlers.
     */
    public function start()
    {
        if (self::$sessionStarted) {
            return;
        }

        // use this object as the session handler
        session_set_save_handler(
            array($this, 'sessionDummy'), // open
            array($this, 'sessionDummy'), // close
            array($this, 'sessionRead'),
            array($this, 'sessionDummy'), // write
            array($this, 'sessionDestroy'),
            array($this, 'sessionDummy') // gc
        );

        parent::start();
    }

    /**
     * {@inheritDoc}
     */
    public function read($key, $default = null)
    {
        if (null !== ($data = $this->db->hget($this->getHashKey(), $key))) {
            return unserialize($data);
        }

        return $default;
    }

    /**
     * {@inheritDoc}
     */
    public function remove($key)
    {
        $retval = $this->db->hget($this->getHashKey(), $key);
        if (null !== $retval) {
            $this->db->hdel($this->getHashKey(), $key);
        }

        return $retval;
    }

    /**
     * {@inheritDoc}
     */
    public function write($key, $data)
    {
        $this->db->hset($this->getHashKey(), $key, serialize($data));

        $expires = (int) $this->options['lifetime'];
        if ($expires > 0) {
            $this->db->expire($this->getHashKey(), $expires);
        }
    }

    /**
     * Dummy session handler for callbacks that don't need to do anything
     */
    public function sessionDummy()
    {
        return true;
    }

    /**
     * Destroys a session.
     *
     * @param  string $id  A session ID
     *
     * @return Boolean  true
     */
    public function sessionDestroy($id)
    {
        $this->db->del($this->getHashKeyForId($id));

        return true;
    }

    /**
     * Reads a session.
     *
     * @param  string $id  A session ID
     * @return string The session data if the session was read or created
     */
    public function sessionRead($id)
    {
        return '';
    }

    /**
     * Prepends the Session ID with a user-defined prefix (if any).
     *
     * @param string $id session id
     * @return string prefixed session ID
     */
    protected function getHashKeyForId($id)
    {
        if (!isset($this->options['prefix'])) {
            return $id;
        }

        return $this->options['prefix'] . ':' . $id;
    }

    /**
     * Prepends the Session ID with a user-defined prefix (if any).
     *
     * @return string prefixed session ID
     */
    protected function getHashKey()
    {
        return $this->getHashKeyForId($this->getId());
    }
}
