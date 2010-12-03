<?php

namespace Bundle\RedisBundle\SessionStorage;

use Symfony\Component\HttpFoundation\SessionStorage\NativeSessionStorage;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Bundle\RedisBundle\RedisClient;

class RedisSessionStorage extends NativeSessionStorage
{
    /**
     * Instance of RedisClient
     * 
     * @var RedisClient
     */
    protected $db;

    /**
     * @throws \InvalidArgumentException When "db_table" option is not provided
     */
    public function __construct(RedisClient $db, $options = null, $prefix = 'session')
    {
        $this->db = $db;
        
        $cookieDefaults = session_get_cookie_params();

        $this->options = array_merge(array(
            'name'          => '_SESSION',
            'lifetime'      => $cookieDefaults['lifetime'],
            'path'          => $cookieDefaults['path'],
            'domain'        => $cookieDefaults['domain'],
            'secure'        => $cookieDefaults['secure'],
            'httponly'      => isset($cookieDefaults['httponly']) ? $cookieDefaults['httponly'] : false,
            'prefix'        => $prefix,
        ), $options);

        session_name($this->options['name']);
    }

    /**
     * Starts the session.
     */
    public function start()
    {    
        if (self::$sessionStarted) {
            return;
        }

        parent::start();
        
        $this->options['id'] = session_id();
    }

    /**
     * Reads a session.
     *
     * @param  string $id  A session ID
     *
     * @return string      The session data if the session was read or created, otherwise an exception is thrown
     *
     * @throws \RuntimeException If the session cannot be read
     */
    public function read($key, $default = null)
    {
        if (null !== $data = $this->db->get($this->getId($key)))
        {
            return unserialize($data);
        }
        return $default;
    }

    /**
     * Writes session data.
     *
     * @param  string $id    A session ID
     * @param  string $data  A serialized chunk of session data
     *
     * @return bool true, if the session was written, otherwise an exception is thrown
     *
     * @throws \RuntimeException If the session data cannot be written
     */
    public function write($key, $data)
    {
        return $this->db->set($this->getId($key), serialize($data));
    }
    
    public function remove($key)
    {
        $this->db->del($this->getId($key));
    }

	/**
	 * Prepends the Session ID with a user-defined prefix (if any).
	 *
	 * @param  string $id   A session ID
	 * 
	 * @return string prefixed session ID
	 */
	protected function getId($id)
	{
		if (!isset($this->options['prefix']))
		{
			return $this->options['id'] . ':' . $id;
		}
		
		return $this->options['prefix'] . ':' . $this->options['id'] . ':' . $id;
	}
}
