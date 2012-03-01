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

use Symfony\Component\HttpFoundation\Session\Storage\AbstractSessionStorage;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Predis\Client;

/**
 * Redis based session storage
 *
 * @author  Justin Rainbow <justin.rainbow@gmail.com>
 * @author  Jordi Boggiano <j.boggiano@seld.be>
 * @author  Henrik Westphal <henrik.westphal@gmail.com>
 */
class RedisSessionStorage extends AbstractSessionStorage implements \SessionHandlerInterface
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
     * {@inheritDoc}
     */
    public function open($savePath, $sessionName)
    {
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function close()
    {
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function read($sessionId)
    {
        return $this->db->get($this->getRedisKey($sessionId)) ?: '';
    }

    /**
     * {@inheritDoc}
     */
    public function write($sessionId, $data)
    {
        $this->db->set($this->getRedisKey($sessionId), $data);

        if (0 < ($expires = (int) $this->options['cookie_lifetime'])) {
            $this->db->expire($this->getRedisKey($sessionId), $expires);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function destroy($sessionId)
    {
        $this->db->del($this->getRedisKey($sessionId));

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function gc($lifetime)
    {
        return true;
    }

    /**
     * Prepends the session ID with a user-defined prefix (if any).
     *
     * @param string $id session id
     * @return string prefixed session ID
     */
    protected function getRedisKey($sessionId)
    {
        if (!isset($this->options['prefix'])) {
            return $sessionId;
        }

        return $this->options['prefix'] . ':' . $sessionId;
    }
}
