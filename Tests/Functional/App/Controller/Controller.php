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
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller
 *
 * @author Niels Keurentjes <niels.keurentjes@omines.com>
 */
class Controller extends AbstractController
{
    public function home(Request $request, \Redis $redis)
    {
        $redis->set('foo', 'bar');

        return new JsonResponse([
            'result' => $redis->get('foo'),
        ]);
    }
}
