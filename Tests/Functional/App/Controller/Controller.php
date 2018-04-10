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

use Snc\RedisBundle\Tests\Functional\App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

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

        return JsonResponse::create([
            'result' => $redis->get('foo'),
        ]);
    }

    public function createUser()
    {
        $user = (new User())
            ->setUsername('foo')
            ->setEmail('bar@example.org')
        ;

        $em = $this->getDoctrine()->getManagerForClass(User::class);
        $em->persist($user);
        $em->flush();

        return JsonResponse::create(['result' => 'ok']);
    }

    public function viewUser()
    {
        $repository = $this->getDoctrine()->getRepository(User::class);

        /** @var User $user */
        $user = $repository->findOneBy(['username' => 'foo']);

        return JsonResponse::create([
            'id' => $user->getId(),
            'username' => $user->getUsername(),
            'email' => $user->getEmail(),
        ]);
    }
}