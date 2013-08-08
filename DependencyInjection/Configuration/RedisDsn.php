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

/**
 * RedisDsn
 */
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
     * @var int
     */
    protected $weight;

    /**
     * @var string
     */
    protected $alias;

    /**
     * Constructor
     *
     * @param string $dsn
     */
    public function __construct($dsn)
    {
        $this->dsn = $dsn;
        $this->parseDsn($dsn);
    }

    /**
     * @return int|null
     */
    public function getDatabase()
    {
        return $this->database;
    }

    /**
     * @return int
     */
    public function getWeight()
    {
        return $this->weight;
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
     * @return string
     */
    public function getAlias()
    {
        return $this->alias;
    }

    /**
     * @return bool
     */
    public function isValid()
    {
        if (0 !== strpos($this->dsn, 'redis://')) {
            return false;
        }

        if (null !== $this->getHost() && null !== $this->getPort()) {
            return true;
        }

        if (null !== $this->getSocket()) {
            return true;
        }

        return false;
    }

    /**
     * @param string $dsn
     */
    protected function parseDsn($dsn)
    {
        $dsn = str_replace('redis://', '', $dsn); // remove "redis://"
        if (false !== $pos = strrpos($dsn, '@')) {
            // parse password
            $this->password = str_replace('\@', '@', substr($dsn, 0, $pos));
            $dsn = substr($dsn, $pos + 1);
        }
        $dsn = preg_replace_callback('/\?(weight|alias)=[^&]+.*$/', array($this, 'parseParameters'), $dsn); // parse parameters
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

    /**
     * @param array $matches
     *
     * @return string
     */
    protected function parseParameters($matches)
    {
        $parameters = explode('&', substr($matches[0], 1));
        foreach ($parameters as $parameter) {
            $kv = explode('=', $parameter, 2);
            if (2 === count($kv)) {
                switch ($kv[0]) {
                    case 'weight':
                        if ($kv[1]) {
                            $this->weight = (int) $kv[1];
                        }
                        break;
                    case 'alias':
                        if ($kv[1]) {
                            $this->alias = $kv[1];
                        }
                        break;
                }
            }
        }

        return '';
    }
}
