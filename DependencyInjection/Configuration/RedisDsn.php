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
     * @var bool
     */
    protected $tls;

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
     * @return bool
     */
    public function getTls()
    {
        return $this->tls;
    }

    /**
     * @return string
     */
    public function getAlias()
    {
        return $this->alias;
    }

    /**
     * @return string
     */
    public function getPersistentId()
    {
        return md5($this->dsn);
    }

    /**
     * @return bool
     */
    public function isValid()
    {
        if (0 !== strpos($this->dsn, 'redis://') && 0 !== strpos($this->dsn, 'rediss://')) {
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
        $dsn = preg_replace('#rediss?://#', '', $dsn); // remove "redis://" and "rediss://"
        if (false !== $pos = strrpos($dsn, '@')) {
            // parse password
            $password = substr($dsn, 0, $pos);

            if (strstr($password, ':')) {
                list(, $password) = explode(':', $password, 2);
            }

            $this->password = urldecode($password);

            $dsn = substr($dsn, $pos + 1);
        }
        $dsn = preg_replace_callback('/\?(.*)$/', array($this, 'parseParameters'), $dsn); // parse parameters
        if (preg_match('#^(.*)/(\d+|%[^%]+%)$#', $dsn, $matches)) {
            // parse database
            $this->database = is_numeric($matches[2]) ? (int) $matches[2] : $matches[2];
            $dsn = $matches[1];
        }
        if (preg_match('#^([^:]+)(:(\d+|%[^%]+%))?$#', $dsn, $matches)) {
            if (!empty($matches[1])) {
                // parse host/ip or socket
                if ('/' === $matches[1][0]) {
                    $this->socket = $matches[1];
                } else {
                    $this->host = $matches[1];
                }
            }
            if (null === $this->socket && !empty($matches[3])) {
                // parse port
                $this->port = is_numeric($matches[3]) ? (int) $matches[3] : $matches[3];
            }
        } elseif (preg_match('#^\[([^\]]+)](:(\d+))?$#', $dsn, $matches)) { // parse enclosed IPv6 address and optional port
            if (!empty($matches[1])) {
                $this->host = $matches[1];
            }
            if (!empty($matches[3])) {
                $this->port = (int) $matches[3];
            }
        }

        $this->tls = 0 === strpos($this->dsn, 'rediss://');
    }

    /**
     * @param array $matches
     *
     * @return string
     */
    protected function parseParameters($matches)
    {
        parse_str($matches[1], $params);

        foreach ($params as $key => $val) {
            if (!$val) {
                continue;
            }
            switch ($key) {
                case 'weight':
                    $this->weight = (int) $val;
                    break;
                case 'alias':
                    $this->alias = $val;
                    break;
            }
        }

        return '';
    }

    public function __toString()
    {
        return $this->dsn;
    }
}
