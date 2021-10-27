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

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Snc\RedisBundle\DataCollector\RedisDataCollector;
use Snc\RedisBundle\Tests\Functional\App\Entity\User;
use Snc\RedisBundle\Tests\Functional\App\Kernel;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Debug\DebugClassLoader;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Response;

/**
 * @author Niels Keurentjes <niels.keurentjes@omines.com>
 */
class IntegrationTest extends WebTestCase
{
    /** @var KernelBrowser */
    private $client;

    /** @var EntityManagerInterface */
    private $em;

    protected function setUp(): void
    {
        // TODO: Drop when we drop symfony 3.4/doctrine-bundle 1.x support
        if (class_exists(DebugClassLoader::class)) {
            DebugClassLoader::disable();
        }

        $fs = new Filesystem();
        $fs->remove(__DIR__ .'/App/var');

        parent::setUp();

        $this->client = static::createClient();

        $kernel = $this->client->getKernel();

        $this->em = $kernel->getContainer()->get('public_doctrine')->getManager();
        $schemaTool = new SchemaTool($this->em);
        $metadata = $this->em->getMetadataFactory()->getAllMetadata();
        $schemaTool->createSchema($metadata);

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
        $this->assertCount(6, $collector->getCommands());
    }

    public function testCreateUser()
    {
        $response = $this->profileRequest('GET', '/user/create');

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testViewUser()
    {
        $user = (new User())
            ->setUsername('foo')
            ->setEmail('bar@example.org')
        ;

        $this->em->persist($user);
        $this->em->flush();
        $this->em->clear();

        $response = $this->profileRequest('GET', '/user/view');

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
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
