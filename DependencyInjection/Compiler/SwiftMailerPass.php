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

use Snc\RedisBundle\SwiftMailer\RedisSpool;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\LogicException;

/**
 * @deprecated
 */
class SwiftMailerPass implements CompilerPassInterface
{
    const SERVICE_ID = 'snc_redis.swiftmailer.spool';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $serviceId = self::SERVICE_ID;
        if ($container->hasDefinition($serviceId)) {
            $handlerDefinition = $container->getDefinition($serviceId);
            if (RedisSpool::class === $handlerDefinition->getClass()) {
                if (!interface_exists('\\Swift_Spool')) {
                    throw new LogicException('SncRedisBundle SwiftMailer integration needs SwiftMailer to be installed');
                }

                // default class, lets check for Swift Mailer version and set correct class
                $class = new \ReflectionClass('\\Swift_Spool');
                $parameters = $class->getMethod('queueMessage')->getParameters();
                $reflectionType = $parameters[0]->getType();

                switch ($reflectionType instanceof \ReflectionType ? $reflectionType->getName() : $reflectionType) {
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
