<?php

namespace Bundle\RedisBundle\SessionStorage;

use Symfony\Component\HttpFoundation\SessionStorage\SessionStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Bundle\RedisBundle\RedisClient;

class RedisSessionStorage implements SessionStorageInterface
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
        
        if (session_id() !== '' && isset($options['id']) && $options['id'] !== '') {
            session_id($options['id']);
        }
        
        $options['prefix'] = $prefix;
        $this->options = $options;
        
        session_start();
        
        $this->options['id'] = session_id();
    }

    /**
     * Starts the session.
     */
    public function start()
    {
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
    public function read($id)
    {
        return unserialize($this->db->get($this->getId($id)));
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
    public function write($id, $data)
    {
        return $this->db->set($this->getId($id), serialize($data));
    }
    
    function remove($key)
    {
        
    }
    
    function regenerate($destroy = false)
    {
        
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
