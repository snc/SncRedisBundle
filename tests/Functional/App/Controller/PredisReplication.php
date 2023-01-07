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

use Predis\Client;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;

final class PredisReplication extends AbstractController
{
    public function __invoke(Client $predisReplication): JsonResponse
    {
        $predisReplication->set('foo', 'bar');

        return new JsonResponse(['result' => $predisReplication->get('foo')]);
    }
}
