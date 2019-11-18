<?php

/*
 * This file is part of the SncRedisBundle package.
 *
 * (c) Henrik Westphal <henrik.westphal@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Snc\RedisBundle\DataCollector;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Kernel;

if (Kernel::VERSION_ID >= 40308) {
    trait DataCollectorSymfonyCompatibilityTrait
    {
        /**
         * {@inheritdoc}
         */
        public function collect(Request $request, Response $response, \Throwable $exception = null)
        {
            $this->data = array(
                'commands' => $this->logger->getCommands(),
            );
        }
    }
} else {
    trait DataCollectorSymfonyCompatibilityTrait
    {
        /**
         * {@inheritdoc}
         */
        public function collect(Request $request, Response $response, \Exception $exception = null)
        {
            $this->data = array(
                'commands' => $this->logger->getCommands(),
            );
        }
    }
}
