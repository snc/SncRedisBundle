<?php

/*
 * This file is part of the SncRedisBundle package.
 *
 * (c) Henrik Westphal <henrik.westphal@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Snc\RedisBundle\DependencyInjection\Configuration;

class RedisDsn
{
    /**
     * @var string
     */
    protected $dsn;

    /**
     * @var string
     */
    protected $password;

    /**
     * @var string
     */
    protected $host;

    /**
     * @var int
     */
    protected $port;

    /**
     * @var string
     */
    protected $socket;

    /**
     * @var int
     */
    protected $database;

    /**
     * Constructor
     *
     * @param $dsn
     */
    public function __construct($dsn)
    {
        $this->dsn = $dsn;
        $this->parseDsn($dsn);
    }

    /**
     * @return int
     */
    public function getDatabase()
    {
        return $this->database ?: 0;
    }

    /**
     * @return string
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @return int
     */
    public function getPort()
    {
        if (null !== $this->socket) {
            return null;
        }

        return $this->port ?: 6379;
    }

    /**
     * @return string
     */
    public function getSocket()
    {
        return $this->socket;
    }

    /**
     * @param $dsn
     */
    protected function parseDsn($dsn)
    {
        $dsn = substr($dsn, 8); // remove "redis://"
        if (false !== $pos = strrpos($dsn, '@')) {
            // parse password
            $this->password = str_replace('\@', '@', substr($dsn, 0, $pos));
            $dsn = substr($dsn, $pos + 1);
        }
        if (preg_match('#^(.*)/(\d+)$#', $dsn, $matches)) {
            // parse database
            $this->database = (int) $matches[2];
            $dsn = $matches[1];
        }
        if (preg_match('#^([^:]+)(:(\d+))?$#', $dsn, $matches)) {
            if (!empty($matches[1])) {
                // parse host/ip or socket
                if ('/' === $matches[1]{0}) {
                    $this->socket = $matches[1];
                } else {
                    $this->host = $matches[1];
                }
            }
            if (null === $this->socket && !empty($matches[3])) {
                // parse port
                $this->port = (int) $matches[3];
            }
        }
    }
}
