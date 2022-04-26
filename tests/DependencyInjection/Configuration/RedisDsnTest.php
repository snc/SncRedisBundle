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

namespace Snc\RedisBundle\Tests\DependencyInjection\Configuration;

use PHPUnit\Framework\TestCase;
use Snc\RedisBundle\DependencyInjection\Configuration\RedisDsn;

class RedisDsnTest extends TestCase
{
    /** @return list<list<string>> */
    public function hostValues(): array
    {
        return [
            ['redis://localhost', 'localhost'],
            ['redis://localhost/1', 'localhost'],
            ['redis://localhost:63790', 'localhost'],
            ['redis://localhost:63790/10', 'localhost'],
            ['redis://pw@localhost:63790/10', 'localhost'],
            ['redis://127.0.0.1', '127.0.0.1'],
            ['redis://127.0.0.1/1', '127.0.0.1'],
            ['redis://127.0.0.1:63790', '127.0.0.1'],
            ['redis://127.0.0.1:63790/10', '127.0.0.1'],
            ['redis://pw@127.0.0.1:63790/10', '127.0.0.1'],
            ['redis://[::1]', '::1'],
            ['redis://[::1]/1', '::1'],
            ['redis://[::1]:63790', '::1'],
            ['redis://[::1]:63790/10', '::1'],
            ['redis://pw@[::1]:63790/10', '::1'],
            ['redis://[1050:0000:0000:0000:0005:0600:300c:326b]', '1050:0000:0000:0000:0005:0600:300c:326b'],
            ['redis://[1050:0000:0000:0000:0005:0600:300c:326b]/1', '1050:0000:0000:0000:0005:0600:300c:326b'],
            ['redis://[1050:0000:0000:0000:0005:0600:300c:326b]:63790', '1050:0000:0000:0000:0005:0600:300c:326b'],
            ['redis://[1050:0000:0000:0000:0005:0600:300c:326b]:63790/10', '1050:0000:0000:0000:0005:0600:300c:326b'],
            ['redis://pw@[1050:0000:0000:0000:0005:0600:300c:326b]:63790/10', '1050:0000:0000:0000:0005:0600:300c:326b'],
            ['redis://[1050:0:0:0:5:600:300c:326b]', '1050:0:0:0:5:600:300c:326b'],
            ['redis://[1050:0:0:0:5:600:300c:326b]/1', '1050:0:0:0:5:600:300c:326b'],
            ['redis://[1050:0:0:0:5:600:300c:326b]:63790', '1050:0:0:0:5:600:300c:326b'],
            ['redis://[1050:0:0:0:5:600:300c:326b]:63790/10', '1050:0:0:0:5:600:300c:326b'],
            ['redis://pw@[1050:0:0:0:5:600:300c:326b]:63790/10', '1050:0:0:0:5:600:300c:326b'],
            ['redis://[ff06:0:0:0:0:0:0:c3]', 'ff06:0:0:0:0:0:0:c3'],
            ['redis://[ff06:0:0:0:0:0:0:c3]/1', 'ff06:0:0:0:0:0:0:c3'],
            ['redis://[ff06:0:0:0:0:0:0:c3]:63790', 'ff06:0:0:0:0:0:0:c3'],
            ['redis://[ff06:0:0:0:0:0:0:c3]:63790/10', 'ff06:0:0:0:0:0:0:c3'],
            ['redis://pw@[ff06:0:0:0:0:0:0:c3]:63790/10', 'ff06:0:0:0:0:0:0:c3'],
            ['redis://[ff06::c3]', 'ff06::c3'],
            ['redis://[ff06::c3]/1', 'ff06::c3'],
            ['redis://[ff06::c3]:63790', 'ff06::c3'],
            ['redis://[ff06::c3]:63790/10', 'ff06::c3'],
            ['redis://pw@[ff06::c3]:63790/10', 'ff06::c3'],
            ['redis://%redis_host%', '%redis_host%'],
            ['redis://%redis_host%/%redis_db%', '%redis_host%'],
            ['redis://%redis_host%:%redis_port%', '%redis_host%'],
            ['redis://%redis_host%:%redis_port%/%redis_db%', '%redis_host%'],
            ['redis://%redis_pass%@%redis_host%:%redis_port%/%redis_db%', '%redis_host%'],
            ['rediss://localhost', 'localhost'],
        ];
    }

    /**
     * @param string $dsn  DSN
     * @param string $host Host
     *
     * @dataProvider hostValues
     */
    public function testHost(string $dsn, string $host): void
    {
        $dsn = new RedisDsn($dsn);
        $this->assertSame($host, $dsn->getHost());
    }

    /** @return list<array{0: string, 1: string}> */
    public static function socketValues(): array
    {
        return [
            ['redis:///redis.sock', '/redis.sock'],
            ['redis:///redis.sock/1', '/redis.sock'],
            ['redis:///redis.sock:63790', '/redis.sock'],
            ['redis:///redis.sock:63790/10', '/redis.sock'],
            ['redis://pw@/redis.sock:63790/10', '/redis.sock'],
            ['redis:///var/run/redis/redis-1.sock', '/var/run/redis/redis-1.sock'],
            ['redis:///var/run/redis/redis-1.sock/1', '/var/run/redis/redis-1.sock'],
            ['redis:///var/run/redis/redis-1.sock:63790', '/var/run/redis/redis-1.sock'],
            ['redis:///var/run/redis/redis-1.sock:63790/10', '/var/run/redis/redis-1.sock'],
            ['redis://pw@/var/run/redis/redis-1.sock:63790/10', '/var/run/redis/redis-1.sock'],
        ];
    }

    /**
     * @param string $dsn    DSN
     * @param string $socket Socket
     *
     * @dataProvider socketValues
     */
    public function testSocket(string $dsn, string $socket): void
    {
        $dsn = new RedisDsn($dsn);
        $this->assertSame($socket, $dsn->getSocket());
    }

    /** @return list<array{0: string, 1:bool}> */
    public function tlsValues(): array
    {
        return [
            ['redis://localhost', false],
            ['rediss://localhost', true],
        ];
    }

    /** @dataProvider tlsValues */
    public function testTls(string $dsn, bool $tls): void
    {
        $dsn = new RedisDsn($dsn);
        $this->assertSame($tls, $dsn->getTls());
    }

    /** @return list<array{0: string, 1: int|string}> */
    public static function portValues(): array
    {
        return [
            ['redis://localhost', 6379],
            ['redis://localhost/1', 6379],
            ['rediss://localhost:6380', 6380],
            ['redis://localhost:63790', 63790],
            ['redis://localhost:63790/10', 63790],
            ['redis://pw@localhost:63790/10', 63790],
            ['redis://127.0.0.1', 6379],
            ['redis://127.0.0.1/1', 6379],
            ['redis://127.0.0.1:63790', 63790],
            ['redis://127.0.0.1:63790/10', 63790],
            ['redis://pw@127.0.0.1:63790/10', 63790],
            ['redis://%redis_host%:%redis_port%', '%redis_port%'],
            ['redis://%redis_host%:%redis_port%/%redis_db%', '%redis_port%'],
            ['redis://%redis_pass%@%redis_host%:%redis_port%/%redis_db%', '%redis_port%'],
            ['redis:///redis.sock', 0],
            ['redis:///redis.sock/1', 0],
            ['redis:///redis.sock:63790', 0],
            ['redis:///redis.sock:63790/10', 0],
            ['redis://pw@/redis.sock:63790/10', 0],
        ];
    }

    /**
     * @param int|string $port Port
     *
     * @dataProvider portValues
     */
    public function testPort(string $dsn, $port): void
    {
        $dsn = new RedisDsn($dsn);
        $this->assertSame($port, $dsn->getPort());
    }

    /** @return array<array{0: string, 1: ?int}> */
    public static function databaseValues(): array
    {
        return [
            ['redis://localhost', null],
            ['redis://localhost/0', 0],
            ['redis://localhost/1', 1],
            ['redis://localhost:63790', null],
            ['redis://localhost:63790/10', 10],
            ['redis://pw@localhost:63790/10', 10],
            ['redis://127.0.0.1', null],
            ['redis://127.0.0.1/0', 0],
            ['redis://127.0.0.1/1', 1],
            ['redis://127.0.0.1:63790', null],
            ['redis://127.0.0.1:63790/10', 10],
            ['redis://pw@127.0.0.1:63790/10', 10],
            ['redis://%redis_host%', null],
            ['redis://%redis_host%/%redis_db%', '%redis_db%'],
            ['redis://%redis_host%:%redis_port%', null],
            ['redis://%redis_host%:%redis_port%/%redis_db%', '%redis_db%'],
            ['redis://pw@%redis_host%:%redis_port%/%redis_db%', '%redis_db%'],
            ['redis:///redis.sock', null],
            ['redis:///redis.sock/0', 0],
            ['redis:///redis.sock/1', 1],
            ['redis:///redis.sock:63790', null],
            ['redis:///redis.sock:63790/10', 10],
            ['redis://pw@/redis.sock:63790/10', 10],
        ];
    }

    /**
     * @param int|string|null $database
     *
     * @dataProvider databaseValues
     */
    public function testDatabase(string $dsn, $database): void
    {
        $dsn = new RedisDsn($dsn);
        $this->assertSame($database, $dsn->getDatabase());
    }

    /** @return array<array{0: string, 1: ?string}> */
    public static function authenticationParametersValues(): array
    {
        return [
            ['redis://localhost', null, null],
            ['redis://localhost/1', null, null],
            ['redis://pw@localhost:63790/10', null, 'pw'],
            ['redis://user:pw@localhost:63790/10', 'user', 'pw'],
            ['redis://user:pw:withcolon@localhost:63790/10', 'user', 'pw:withcolon'],
            ['redis://Pw%3AColon%25@localhost:63790/10', null, 'Pw:Colon%'],
            ['redis://p%40w@localhost:63790/10', null, 'p@w'],
            ['redis://mB(.z9},6o?zl>v!LM76A]lCg77,;.@localhost:63790/10', null, 'mB(.z9},6o?zl>v!LM76A]lCg77,;.'],
            ['redis://127.0.0.1', null, null],
            ['redis://127.0.0.1/1', null, null],
            ['redis://pw@127.0.0.1:63790/10', null, 'pw'],
            ['redis://p%40w@127.0.0.1:63790/10', null, 'p@w'],
            ['redis://mB(.z9},6o?zl>v!LM76A]lCg77,;.@127.0.0.1:63790/10', null, 'mB(.z9},6o?zl>v!LM76A]lCg77,;.'],
            ['redis://%redis_host%', null, null],
            ['redis://%redis_host%/%redis_db%', null, null],
            ['redis://%redis_pass%@%redis_host%:%redis_port%', null, '%redis_pass%'],
            ['redis:///redis.sock', null, null],
            ['redis:///redis.sock/1', null, null],
            ['redis://pw@/redis.sock/10', null, 'pw'],
            ['redis://p%40w@/redis.sock/10', null, 'p@w'],
            ['redis://mB(.z9},6o?zl>v!LM76A]lCg77,;.@/redis.sock/10', null, 'mB(.z9},6o?zl>v!LM76A]lCg77,;.'],
        ];
    }

    /** @dataProvider authenticationParametersValues */
    public function testAuthenticationParameters(string $dsn, ?string $username, ?string $password): void
    {
        $dsn = new RedisDsn($dsn);
        $this->assertSame($username, $dsn->getUsername());
        $this->assertSame($password, $dsn->getPassword());
    }

    /** @return array<array{0: string, 1: bool}> */
    public static function isValidValues(): array
    {
        return [
            ['redis://localhost', true],
            ['rediss://localhost', true],
            ['redis://localhost/1', true],
            ['redis://pw@localhost:63790/10', true],
            ['redis://127.0.0.1', true],
            ['redis://127.0.0.1/1', true],
            ['redis://pw@127.0.0.1:63790/10', true],
            ['redis:///redis.sock', true],
            ['redis:///redis.sock/1', true],
            ['redis://pw@/redis.sock/10', true],
            ['redis://pw@/redis.sock/10', true],
            ['redis://%redis_host%', true],
            ['redis://%redis_host%/%redis_db%', true],
            ['redis://%redis_host%:%redis_port%', true],
            ['redis://%redis_host%:%redis_port%/%redis_db%', true],
            ['redis://%redis_pass%@%redis_host%:%redis_port%/%redis_db%', true],
            ['localhost', false],
            ['localhost/1', false],
            ['pw@localhost:63790/10', false],
        ];
    }

    /**
     * @param string $dsn   DSN
     * @param bool   $valid Valid
     *
     * @dataProvider isValidValues
     */
    public function testIsValid(string $dsn, bool $valid): void
    {
        $dsn = new RedisDsn($dsn);
        $this->assertSame($valid, $dsn->isValid());
    }

    /** @return array<array{0: string, 1: ?int, 2: ?string}> */
    public static function parameterValues(): array
    {
        return [
            ['redis://localhost', null, null],
            ['redis://localhost/1?weight=1&alias=master', 1, 'master'],
            ['redis://pw@localhost:63790/10?alias=master&weight=2', 2, 'master'],
            ['redis://127.0.0.1?weight=3', 3, null],
            ['redis://127.0.0.1/1?alias=master&weight=4', 4, 'master'],
            ['redis://pw@127.0.0.1:63790/10?weight=5&alias=master', 5, 'master'],
            ['redis:///redis.sock?weight=6&alias=master', 6, 'master'],
            ['redis:///redis.sock/1?weight=7', 7, null],
            ['redis://pw@/redis.sock/10?weight=8&alias=master', 8, 'master'],
            ['redis://pw@/redis.sock/10?alias=master&weight=9', 9, 'master'],
            ['redis://localhost?alias=master', null, 'master'],
        ];
    }

    /** @dataProvider parameterValues */
    public function testParameterValues(string $dsn, ?int $weight, ?string $alias): void
    {
        $dsn = new RedisDsn($dsn);
        $this->assertSame($weight, $dsn->getWeight());
        $this->assertSame($alias, $dsn->getAlias());
    }
}
