<?php

/*
 * This file is part of the SncRedisBundle package.
 *
 * (c) Henrik Westphal <henrik.westphal@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Snc\RedisBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class SwiftMailerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $serviceId = 'snc_redis.swiftmailer.spool';
        if ($container->hasDefinition($serviceId)) {
            $handlerDefinition = $container->getDefinition($serviceId);
            if ('Snc\\RedisBundle\\SwiftMailer\\RedisSpool' === $handlerDefinition->getClass()) {
                // default class, lets check for Swift Mailer version and set correct class
                $class = new \ReflectionClass('\\Swift_Spool');
                $parameters = $class->getMethod('queueMessage')->getParameters();
                switch ($parameters[0]->getClass()->getName()) {
                    case 'Swift_Mime_Message':
                        // Swift Mailer 5.x
                        $handlerDefinition->setClass($handlerDefinition->getClass().'5');
                        break;
                    case 'Swift_Mime_SimpleMessage':
                        // Swift Mailer 6.x
                        $handlerDefinition->setClass($handlerDefinition->getClass().'6');
                        break;
                    default:
                        throw new \RuntimeException('Failed to detect the current Swift Mailer version.');
                }
            }
        }
    }
}
