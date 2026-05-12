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

namespace Snc\RedisBundle\Tests\Functional\App\Controller;

use Predis\ClientInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;

class Controller extends AbstractController
{
    public function __construct(#[Autowire(service: 'snc_redis.cluster')] private ClientInterface $cluster)
    {
    }

    public function __invoke(): JsonResponse
    {
        $this->cluster->set('foo', 'bar');

        return new JsonResponse([
            'result' => $this->cluster->get('foo'),
        ]);
    }
}
