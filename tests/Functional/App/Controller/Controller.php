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

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;

class Controller extends AbstractController
{
    /** @param iterable<object> $clients */
    public function __construct(private iterable $clients)
    {
    }

    public function __invoke(): JsonResponse
    {
        $result = null;
        foreach ($this->clients as $client) {
            /** @psalm-suppress MixedMethodCall */
            $client->set('foo', 'bar');
            /** @psalm-suppress MixedMethodCall */
            $result = $client->get('foo');
        }

        return new JsonResponse(['result' => $result]);
    }
}
