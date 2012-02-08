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
     * Starts the session.
     */
    public function start()
    {
        if (self::$sessionStarted) {
            return;
        }

        // use this object as the session handler
        session_set_save_handler(
            array($this, 'sessionOpen'),
            array($this, 'sessionClose'),
            array($this, 'sessionRead'),
            array($this, 'sessionWrite'),
            array($this, 'sessionDestroy'),
            array($this, 'sessionGC')
        );

        parent::start();
    }

    /**
     * Opens a session.
     *
     * @param  string $path  (ignored)
     * @param  string $name  (ignored)
     *
     * @return Boolean true, if the session was opened, otherwise an exception is thrown
     */
    public function sessionOpen($path = null, $name = null)
    {
        return true;
    }

    /**
     * Closes a session.
     *
     * @return Boolean true, if the session was closed, otherwise false
     */
    public function sessionClose()
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
        $this->db->del($this->getKey($id));

        return true;
    }

    /**
     * Cleans up old sessions.
     *
     * Dummy function since this is handled in write with EXPIRE
     *
     * @param  int $lifetime  The lifetime of a session
     *
     * @return Boolean true
     */
    public function sessionGC($lifetime)
    {
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
        try {
            $data = $this->db->get($this->getKey($id));
        } catch (\Predis\ServerException $e) {
            $data = false;
        }

        // BC for older redis session handler if we get an invalid read, most likely it's a hash
        if (false === $data) {
            if ($data = $this->db->hgetall($this->getKey($id))) {
                foreach ($data as $key => $val) {
                    $_SESSION[$key] = @unserialize($val);
                }
                $data = session_encode();

                // migrate data to new format
                $this->db->del($this->getKey($id));
                $this->sessionWrite($id, $data);
            }
        }

        return (string) $data;
    }

    /**
     * Writes session data.
     *
     * @param  string $id    A session ID
     * @param  string $data  A serialized chunk of session data
     * @return Boolean whether the session was written
     */
    public function sessionWrite($id, $data)
    {
        $result = $this->db->set($this->getKey($id), $data);

        $expires = (int) $this->options['lifetime'];
        if ($expires > 0) {
            $this->db->expire($this->getKey($id), $expires);
        }

        return $result;
    }

    /**
     * Prepends the Session ID with a user-defined prefix (if any).
     *
     * @param string $id session id
     * @return string prefixed session ID
     */
    protected function getKey($id)
    {
        if (!isset($this->options['prefix'])) {
            return $id;
        }

        return $this->options['prefix'] . ':' . $id;
    }
}
