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
use Snc\RedisBundle\Tests\Functional\App\Kernel;
use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Profiler\Profile;

/**
 * IntegrationTest
 *
 * @author Niels Keurentjes <niels.keurentjes@omines.com>
 */
class IntegrationTest extends WebTestCase
{
    /** @var Client */
    private $client;

    protected function setUp()
    {
        $this->client = static::createClient();
    }

    public function testIntegration()
    {
        $response = $this->profileRequest('GET', '/');

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());

        /** @var RedisDataCollector $collector */
        $collector = $this->client->getProfile()->getCollector('redis');
        $this->assertInstanceOf(RedisDataCollector::class, $collector);
        $this->assertCount(2, $collector->getCommands());
    }

    public function testCreateUser()
    {
        $response = $this->profileRequest('GET', '/user/create');

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testViewUser()
    {
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

    /**
     * Manage schema and cleanup chores
     */
    public static function setUpBeforeClass()
    {
        static::deleteTmpDir();

        /** @var EntityManagerInterface $em */
        $em = static::createClient()->getKernel()->getContainer()->get('doctrine')->getManager();
        $schemaTool = new SchemaTool($em);
        $metadata = $em->getMetadataFactory()->getAllMetadata();
        $schemaTool->createSchema($metadata);
    }

    public static function tearDownAfterClass()
    {
        static::deleteTmpDir();
    }

    protected static function deleteTmpDir()
    {
        if (!file_exists($dir = __DIR__ .'/App/var')) {
            return;
        }

        $fs = new Filesystem();
        $fs->remove($dir);
    }

    protected static function getKernelClass()
    {
        require_once __DIR__ . '/App/Kernel.php';

        return Kernel::class;
    }
}