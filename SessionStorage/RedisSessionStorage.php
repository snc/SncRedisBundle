<?php

namespace Snc\RedisBundle\SessionStorage;

use Symfony\Component\HttpFoundation\SessionStorage\NativeSessionStorage;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Predis\Client;

/**
 * Redis based session storage
 *
 * @link    http://github.com/justinrainbow/
 * @author  Justin Rainbow <justin.rainbow@gmail.com>
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
        if (null !== ($data = $this->db->get($this->createId($key))))
        {
            return @unserialize($data);
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
        $id = $this->createId($key);
        
        $result = $this->db->set($id, serialize($data));
        
        $this->db->expire($id, (int) $this->options['lifetime']);
        
        return $result;
    }

    /**
     * Deletes the provided session key.
     *
     * @param  string $id   A session ID
     *
     * @return bool   true, if the session data was deleted
     */
    public function remove($key)
    {
        return $this->db->del($this->createId($key));
    }
    
    public function regenerate($destroy = false)
    {
        $this->db->del($this->createId('_symfony2'));
        
        return parent::regenerate($destroy);
    }

    /**
     * Prepends the Session ID with a user-defined prefix (if any).
     *
     * @param  string $id   A session ID
     *
     * @return string prefixed session ID
     */
    protected function createId($id)
    {
        if (!isset($this->options['prefix'])) {
            return $this->getId() . ':' . $id;
        }

        return $this->options['prefix'] . ':' . $this->getId() . ':' . $id;
    }
}
