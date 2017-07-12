<?php

namespace Snc\RedisBundle\Tests\DependencyInjection\Configuration;

use Snc\RedisBundle\DependencyInjection\Configuration\RedisEnvDsn;

class RedisEnvDsnTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @static
     *
     * @return array
     */
    public static function isValidValues()
    {
        return array(
            array('redis://localhost', false),
            array('redis://localhost/1', false),
            array('redis://pw@localhost:63790/10', false),
            array('redis://127.0.0.1', false),
            array('redis://127.0.0.1/1', false),
            array('redis://pw@127.0.0.1:63790/10', false),
            array('redis:///redis.sock', false),
            array('redis:///redis.sock/1', false),
            array('redis://pw@/redis.sock/10', false),
            array('redis://pw@/redis.sock/10', false),
            array('redis://%redis_host%', false),
            array('redis://%redis_host%/%redis_db%', false),
            array('redis://%redis_host%:%redis_port%', false),
            array('redis://%redis_host%:%redis_port%/%redis_db%', false),
            array('redis://%redis_pass%@%redis_host%:%redis_port%/%redis_db%', false),
            array('redis://env_REDIS_HOST_1ef60d9ef7a55747f99d0a42206e58ed', false),
            array('redis://env_REDIS_HOST_1ef60d9ef7a55747f99d0a42206e58ed/env_REDIS_DB_0d1da5bfb707f91e21a1f78cd11fcd0a', false),
            array('redis://env_REDIS_HOST_1ef60d9ef7a55747f99d0a42206e58ed:env_REDIS_PORT_0458150d4bf631c8ac63b0fa4d257a21', false),
            array('redis://env_REDIS_HOST_1ef60d9ef7a55747f99d0a42206e58ed:env_REDIS_PORT_0458150d4bf631c8ac63b0fa4d257a21/env_REDIS_DB_0d1da5bfb707f91e21a1f78cd11fcd0a', false),
            array('redis://env_REDIS_PW_e7406513a853fd4692343d101baecb7c@env_REDIS_HOST_1ef60d9ef7a55747f99d0a42206e58ed:env_REDIS_PORT_0458150d4bf631c8ac63b0fa4d257a21/env_REDIS_DB_0d1da5bfb707f91e21a1f78cd11fcd0a', false),
            array('localhost', false),
            array('localhost/1', false),
            array('pw@localhost:63790/10', false),
            array('env_REDIS_URL_z07910a06a086c83ba41827aa00b26ed', false),
            array('env_REDIS_URL_e07910a06a086c83ba41827aa00b26ed', true),
        );
    }

    /**
     * @param string $dsn   DSN
     * @param bool   $valid Valid
     *
     * @dataProvider isValidValues
     */
    public function testIsValid($dsn, $valid)
    {
        $dsn = new RedisEnvDsn($dsn);
        $this->assertSame($valid, $dsn->isValid());
    }

    /**
     * @param string $dsn DSN
     *
     * @dataProvider isValidValues
     */
    public function testAliasIsNull($dsn)
    {
        $dsn = new RedisEnvDsn($dsn);
        $this->assertNull($dsn->getAlias());
    }

    /**
     * @param string $dsn DSN
     *
     * @dataProvider isValidValues
     */
    public function testDsnIsUnmodified($providedDsn)
    {
        $dsn = new RedisEnvDsn($providedDsn);
        $this->assertEquals($providedDsn, $dsn->getDsn());
    }
}
