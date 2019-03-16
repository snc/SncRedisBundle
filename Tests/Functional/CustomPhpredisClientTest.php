<?php

/*
 * This file is part of the SncRedisBundle package.
 *
 * (c) Henrik Westphal <henrik.westphal@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Snc\RedisBundle\Tests\Functional;

use Snc\RedisBundle\Client\Phpredis\Client;
use Snc\RedisBundle\Factory\PhpredisClientFactory;
use Snc\RedisBundle\Logger\RedisLogger;
use Snc\RedisBundle\Tests\Functional\App\KernelWithCustomRedisClient;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @author Javier Spagnoletti <phansys@gmail.com>
 */
class CustomPhpredisClientTest extends WebTestCase
{
    public function testCreateFullConfig()
    {
        $logger = $this->getMockBuilder(RedisLogger::class)->getMock();
        $factory = new PhpredisClientFactory($logger);

        $this->assertTrue(class_exists(Client::class));

        $client = $factory->create(
            Client::class,
            'redis://localhost:6379',
            array(
                'connection_timeout' => 10,
                'connection_persistent' => true,
                'prefix' => 'toto',
                'serialization' => 'php',
                'read_write_timeout' => 4,
                'parameters' => [
                    'database' => 3,
                    'password' => 'secret',
                ],
            ),
            'alias_test'
        );

        $this->assertInstanceOf(Client::class, $client);
        $this->assertSame('toto', $client->getOption(\Redis::OPT_PREFIX));
        $this->assertSame(1, $client->getOption(\Redis::OPT_SERIALIZER));
        $this->assertSame(4., $client->getOption(\Redis::OPT_READ_TIMEOUT));
        $this->assertSame(3, $client->getDBNum());
        $this->assertSame('secret', $client->getAuth());
        $this->assertAttributeSame($logger, 'logger', $client);
    }

    /**
     * Manage schema and cleanup chores
     */
    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        static::deleteTmpDir();

        static::createClient()->getKernel();
    }

    public static function tearDownAfterClass()
    {
        static::deleteTmpDir();

        parent::tearDownAfterClass();
    }

    protected static function deleteTmpDir()
    {
        $dir = __DIR__ .'/App/var';
        if (!file_exists($dir)) {
            return;
        }

        $fs = new Filesystem();
        $fs->remove($dir);
    }

    protected static function getKernelClass()
    {
        return KernelWithCustomRedisClient::class;
    }
}
