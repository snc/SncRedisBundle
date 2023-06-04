<?php

declare(strict_types=1);

/*
 * This file is part of the SncRedisBundle package.
 *
 * (c) Henrik Westphal <henrik.westphal@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Snc\RedisBundle\DependencyInjection\Configuration;

use function explode;
use function is_numeric;
use function md5;
use function parse_str;
use function preg_match;
use function preg_replace;
use function preg_replace_callback;
use function strpos;
use function strrpos;
use function strstr;
use function substr;
use function urldecode;

class RedisDsn
{
    protected string $dsn;

    protected ?string $username = null;

    protected ?string $password = null;

    protected ?string $host = null;

    /** @var int|string */
    protected $port = 6379;

    protected ?string $socket = null;

    protected bool $tls = false;

    /** @var string|int|null */
    protected $database = null;

    protected ?int $weight = null;

    protected ?string $alias = null;

    protected ?string $role = null;

    public function __construct(string $dsn)
    {
        $this->dsn = $dsn;
        $this->parseDsn($dsn);
    }

    /** @return int|string|null */
    public function getDatabase()
    {
        return $this->database;
    }

    public function getWeight(): ?int
    {
        return $this->weight;
    }

    public function getHost(): ?string
    {
        return $this->host;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    /** @return string|int */
    public function getPort()
    {
        if ($this->socket !== null) {
            return 0;
        }

        return $this->port ?: 6379;
    }

    public function getSocket(): ?string
    {
        return $this->socket;
    }

    public function getTls(): bool
    {
        return $this->tls;
    }

    public function getAlias(): ?string
    {
        return $this->alias;
    }

    public function getRole(): ?string
    {
        return $this->role;
    }

    public function getPersistentId(): string
    {
        return md5($this->dsn);
    }

    public function isValid(): bool
    {
        if (strpos($this->dsn, 'redis://') !== 0 && strpos($this->dsn, 'rediss://') !== 0) {
            return false;
        }

        if ($this->getHost() !== null && $this->getPort() !== null) {
            return true;
        }

        return $this->getSocket() !== null;
    }

    protected function parseDsn(string $dsn): void
    {
        $dsn = preg_replace('#rediss?://#', '', $dsn); // remove "redis://" and "rediss://"
        $pos = strrpos($dsn, '@');
        if ($pos !== false) {
            // parse username and password
            $username = null;
            $password = substr($dsn, 0, $pos);

            if (strstr($password, ':')) {
                [$username, $password] = explode(':', $password, 2);
            }

            $this->username = $username ? urldecode($username) : null;
            $this->password = urldecode($password);

            $dsn = substr($dsn, $pos + 1);
        }

        $dsn = preg_replace_callback('/\?(.*)$/', [$this, 'parseParameters'], $dsn); // parse parameters
        if (preg_match('#^(.*)/(\d+|%[^%]+%)$#', $dsn, $matches)) {
            // parse database
            $this->database = is_numeric($matches[2]) ? (int) $matches[2] : $matches[2];
            $dsn            = $matches[1];
        }

        if (preg_match('#^([^:]+)(:(\d+|%[^%]+%))?$#', $dsn, $matches)) {
            if (!empty($matches[1])) {
                // parse host/ip or socket
                if ($matches[1][0] === '/') {
                    $this->socket = $matches[1];
                } else {
                    $this->host = $matches[1];
                }
            }

            if ($this->socket === null && !empty($matches[3])) {
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

        $this->tls = strpos($this->dsn, 'rediss://') === 0;
    }

    /** @param mixed[] $matches */
    protected function parseParameters(array $matches): string
    {
        parse_str($matches[1], $params);

        if (!empty($params['weight'])) {
            $this->weight = (int) $params['weight'];
        }

        if (!empty($params['alias'])) {
            $this->alias = $params['alias'];
        }

        if (!empty($params['role'])) {
            $this->role = $params['role'];
        }

        return '';
    }

    public function __toString(): string
    {
        return $this->dsn;
    }
}
