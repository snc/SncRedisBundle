<?php

/*
 * This file is part of the SncRedisBundle package.
 *
 * (c) Henrik Westphal <henrik.westphal@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Snc\RedisBundle\Tests\DependencyInjection\Configuration;

use Snc\RedisBundle\DependencyInjection\Configuration\RedisDsn;

class RedisDsnTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @static
     * @return array
     */
    public static function hostValues()
    {
        return array(
            array('redis://localhost', 'localhost'),
            array('redis://localhost:63790', 'localhost'),
            array('redis://localhost:63790/10', 'localhost'),
            array('redis://pw@localhost:63790/10', 'localhost'),
            array('redis://127.0.0.1', '127.0.0.1'),
            array('redis://127.0.0.1:63790', '127.0.0.1'),
            array('redis://127.0.0.1:63790/10', '127.0.0.1'),
            array('redis://pw@127.0.0.1:63790/10', '127.0.0.1'),
        );
    }

    /**
     * @dataProvider hostValues
     * @param $dns
     * @param $host
     */
    public function testHost($dns, $host)
    {
        $dsn = new RedisDsn($dns);
        $this->assertSame($host, $dsn->getHost());
    }

    /**
     * @static
     * @return array
     */
    public static function socketValues()
    {
        return array(
            array('redis:///redis.sock', '/redis.sock'),
            array('redis:///redis.sock:63790', '/redis.sock'),
            array('redis:///redis.sock:63790/10', '/redis.sock'),
            array('redis://pw@/redis.sock:63790/10', '/redis.sock'),
            array('redis:///var/run/redis/redis-1.sock', '/var/run/redis/redis-1.sock'),
            array('redis:///var/run/redis/redis-1.sock:63790', '/var/run/redis/redis-1.sock'),
            array('redis:///var/run/redis/redis-1.sock:63790/10', '/var/run/redis/redis-1.sock'),
            array('redis://pw@/var/run/redis/redis-1.sock:63790/10', '/var/run/redis/redis-1.sock'),
        );
    }

    /**
     * @dataProvider socketValues
     * @param $dns
     * @param $socket
     */
    public function testSocket($dns, $socket)
    {
        $dsn = new RedisDsn($dns);
        $this->assertSame($socket, $dsn->getSocket());
    }

    /**
     * @static
     * @return array
     */
    public static function portValues()
    {
        return array(
            array('redis://localhost', 6379),
            array('redis://localhost:63790', 63790),
            array('redis://localhost:63790/10', 63790),
            array('redis://pw@localhost:63790/10', 63790),
            array('redis://127.0.0.1', 6379),
            array('redis://127.0.0.1:63790', 63790),
            array('redis://127.0.0.1:63790/10', 63790),
            array('redis://pw@127.0.0.1:63790/10', 63790),
            array('redis:///redis.sock', null),
            array('redis:///redis.sock:63790', null),
            array('redis:///redis.sock:63790/10', null),
            array('redis://pw@/redis.sock:63790/10', null),
        );
    }

    /**
     * @dataProvider portValues
     * @param $dns
     * @param $port
     */
    public function testPort($dns, $port)
    {
        $dsn = new RedisDsn($dns);
        $this->assertSame($port, $dsn->getPort());
    }

    /**
     * @static
     * @return array
     */
    public static function databaseValues()
    {
        return array(
            array('redis://localhost', 0),
            array('redis://localhost:63790', 0),
            array('redis://localhost:63790/10', 10),
            array('redis://pw@localhost:63790/10', 10),
            array('redis://127.0.0.1', 0),
            array('redis://127.0.0.1:63790', 0),
            array('redis://127.0.0.1:63790/10', 10),
            array('redis://pw@127.0.0.1:63790/10', 10),
            array('redis:///redis.sock', 0),
            array('redis:///redis.sock:63790', 0),
            array('redis:///redis.sock:63790/10', 10),
            array('redis://pw@/redis.sock:63790/10', 10),
        );
    }

    /**
     * @dataProvider databaseValues
     * @param $dns
     * @param $database
     */
    public function testDatabase($dns, $database)
    {
        $dsn = new RedisDsn($dns);
        $this->assertSame($database, $dsn->getDatabase());
    }

    /**
     * @static
     * @return array
     */
    public static function passwordValues()
    {
        return array(
            array('redis://localhost', null),
            array('redis://pw@localhost:63790/10', 'pw'),
            array('redis://p\@w@localhost:63790/10', 'p@w'),
            array('redis://mB(.z9},6o?zl>v!LM76A]lCg77,;.@localhost:63790/10', 'mB(.z9},6o?zl>v!LM76A]lCg77,;.'),
            array('redis://127.0.0.1', null),
            array('redis://pw@127.0.0.1:63790/10', 'pw'),
            array('redis://p\@w@127.0.0.1:63790/10', 'p@w'),
            array('redis://mB(.z9},6o?zl>v!LM76A]lCg77,;.@127.0.0.1:63790/10', 'mB(.z9},6o?zl>v!LM76A]lCg77,;.'),
            array('redis:///redis.sock', null),
            array('redis://pw@/redis.sock/10', 'pw'),
            array('redis://p\@w@/redis.sock/10', 'p@w'),
            array('redis://mB(.z9},6o?zl>v!LM76A]lCg77,;.@/redis.sock/10', 'mB(.z9},6o?zl>v!LM76A]lCg77,;.'),
        );
    }

    /**
     * @dataProvider passwordValues
     * @param $dns
     * @param $password
     */
    public function testPassword($dns, $password)
    {
        $dsn = new RedisDsn($dns);
        $this->assertSame($password, $dsn->getPassword());
    }
}
