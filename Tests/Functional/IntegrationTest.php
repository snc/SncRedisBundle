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

/**
 * @author Niels Keurentjes <niels.keurentjes@omines.com>
 */
class IntegrationTest extends WebTestCase
{
    /** @var KernelBrowser */
    private $client;

    protected function setUp(): void
    {
        $fs = new Filesystem();
        $fs->remove(__DIR__ .'/App/var');

        parent::setUp();

        $this->client = static::createClient();

        $kernel = $this->client->getKernel();

        // Clear Redis databases
        $application = new Application($kernel);
        $command = $application->find('redis:flushall');
        $commandTester = new CommandTester($command);
        $this->assertSame(0, $commandTester->execute(array('command' => $command->getName(), '-n' => true)));
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->client = null;
        $this->em = null;
    }

    public function testIntegration()
    {
        $response = $this->profileRequest('GET', '/');

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());

        /** @var RedisDataCollector $collector */
        $collector = $this->client->getProfile()->getCollector('redis');
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
