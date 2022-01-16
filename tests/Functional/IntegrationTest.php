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

use Snc\RedisBundle\DataCollector\RedisDataCollector;
use Snc\RedisBundle\Tests\Functional\App\Kernel;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Response;

use function assert;

class IntegrationTest extends WebTestCase
{
    private ?KernelBrowser $client = null;

    /**
     * Muted deprecation "Passing null to parameter #1 ($async) of type bool is deprecated" - would be fixed by next phpredis release with fixed reflection on its own
     *
     * @group legacy
     */
    protected function setUp(): void
    {
        $fs = new Filesystem();
        $fs->remove(__DIR__ . '/App/var');

        parent::setUp();

        $this->client = static::createClient();

        $kernel = $this->client->getKernel();

        // Clear Redis databases
        $application   = new Application($kernel);
        $command       = $application->find('redis:query');
        $commandTester = new CommandTester($command);
        $this->assertSame(0, $commandTester->execute(['query' => ['flushall'], '-n' => true]));
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->client = null;
    }

    public function testIntegration(): void
    {
        $response = $this->profileRequest('GET', '/');

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());

        $collector = $this->client->getProfile()->getCollector('redis');
        assert($collector instanceof RedisDataCollector);
        $this->assertInstanceOf(RedisDataCollector::class, $collector);
        $this->assertCount(5, $collector->getCommands());
    }

    private function profileRequest(string $method, string $uri): Response
    {
        $client = $this->client;
        $client->enableProfiler();
        $client->request($method, $uri);

        return $client->getResponse();
    }

    protected static function getKernelClass(): string
    {
        require_once __DIR__ . '/App/Kernel.php';

        return Kernel::class;
    }
}
