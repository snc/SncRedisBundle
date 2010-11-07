<?php

namespace Bundle\RedisBundle\SessionStorage;

use Symfony\Component\HttpFoundation\SessionStorage\NativeSessionStorage;
use Symfony\Component\DependencyInjection\ContainerInterface;

class RedisSessionStorage extends NativeSessionStorage
{
    /**
     * 
     */
    protected $container;
    protected $db;

    /**
     * @throws \InvalidArgumentException When "db_table" option is not provided
     */
    public function __construct(ContainerInterface $container, $options = null)
    {
        $this->container = $container;

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
     * @return boolean true, if the session was opened, otherwise an exception is thrown
     */
    public function sessionOpen($path = null, $name = null)
    {
        return true;
    }

    /**
     * Closes a session.
     *
     * @return boolean true, if the session was closed, otherwise false
     */
    public function sessionClose()
    {
        // do nothing
        return true;
    }

    /**
     * Destroys a session.
     *
     * @param  string $id  A session ID
     *
     * @return bool   true, if the session was destroyed, otherwise an exception is thrown
     *
     * @throws \RuntimeException If the session cannot be destroyed
     */
    public function sessionDestroy($id)
    {
        $this->getDb()->delete($this->getId($id));

        return true;
    }

    /**
     * Cleans up old sessions.
     *
     * @param  int $lifetime  The lifetime of a session
     *
     * @return bool true, if old sessions have been cleaned, otherwise an exception is thrown
     *
     * @throws \RuntimeException If any old sessions cannot be cleaned
     */
    public function sessionGC($lifetime)
    {
        return true;
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
    public function sessionRead($id)
    {
        return $this->getDb()->get($this->getId($id));
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
    public function sessionWrite($id, $data)
    {
        return $this->getDb()->set($this->getId($id), $data);
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
			return $id;
		}
		
		return $this->options['prefix'] . ':' . $id;
	}
	
	protected function getDb()
	{
	    if (!$this->db) {
	        $this->db = $this->container->get('redis_connection');
	    }
	    return $this->db;
	}
}
