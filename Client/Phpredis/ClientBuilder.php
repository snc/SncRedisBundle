<?php

/*
 * This file is part of the SncRedisBundle package.
 *
 * (c) Henrik Westphal <henrik.westphal@gmail.com>
 * (c) Yassine Khial <yassine.khial@blablacar.com>
 * (c) Pierre Boudelle <pierre.boudelle@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Snc\RedisBundle\Client\Phpredis;

/**
 * @author Javier Spagnoletti <phansys@gmail.com>
 */
final class ClientBuilder
{
    private $redisClass;

    /**
     * @param string $redisClass
     */
    public function __construct($redisClass = '\Redis')
    {
        $this->redisClass = $redisClass;
    }

    /**
     * @return srting
     */
    public function getClassContents()
    {
        $templatesDir = __DIR__.'/../../Resources/client_templates/';
        $version = phpversion('redis');
        if (version_compare($version, '4.0.0') >= 0) {
            $template = file_get_contents($templatesDir.'ClientV4.php.tpl');
            $template = str_replace(['{{version}}', '{{public_methods}}'], [phpversion('redis'), $this->generateRedisMethods()], $template);
        } elseif (version_compare($version, '3.0.0') >= 0) {
            $template = file_get_contents($templatesDir.'ClientV3.php.tpl');
        } elseif (version_compare($version, '2.0.0') >= 0) {
            $template = file_get_contents($templatesDir.'ClientV2.php.tpl');
        }

        return str_replace('{{extended_classname}}', $this->redisClass, $template);
    }

    /**
     * @return srting
     */
    private function generateRedisMethods()
    {
        $methods = '';
        $rc = new \ReflectionClass($this->redisClass);
        $reflectedMethods = $rc->getMethods(\ReflectionMethod::IS_PUBLIC);

        /* @var $reflectedMethod \ReflectionMethod */
        foreach ($reflectedMethods as $reflectedMethod) {
            if ($reflectedMethod->isFinal() || $reflectedMethod->isConstructor()) {
                continue;
            }

            $method = "\n    /**\n";
            $method .= "     * {@inheritdoc}\n";
            $method .= "     */\n";

            $method .= '    ';

            if ($reflectedMethod->isAbstract()) {
                $method .= 'abstract ';
            }
            $method .= 'public ';
            if ($reflectedMethod->isStatic()) {
                $method .= 'static ';
            }
            $method .= 'function ';
            $method .= $reflectedMethod->getName();
            $method .= '(';
            $args = $reflectedMethod->getParameters();
            /* @var $rp \ReflectionParameter */
            foreach ($args as $rp) {
                if ($rp->getType()) {
                    $method .= $rp->getType();
                    $method .= ' ';
                }
                $isVariadic = false;
                if ($rp->isVariadic()) {
                    $method .= '...';
                    $isVariadic = true;
                }
                if ($rp->isPassedByReference()) {
                    $method .= '&';
                }
                $method .= '$';
                $method .= $rp->getName();
                if ($rp->isDefaultValueAvailable() || $rp->isOptional() && !$isVariadic) {
                    $method .= ' = ';
                    $method .= $rp->isDefaultValueAvailable() ? $rp->getDefaultValue() : 'null';
                }
                if ($rp->getPosition() < count($reflectedMethod->getParameters()) - 1) {
                    $method .= ', ';
                }
            }

            $method .= ')';

            if ($reflectedMethod->hasReturnType()) {
                $method .= ': ';
                $method .= $reflectedMethod->getReturnType();
            }

            $method .= "\n";
            $method .= "    {\n";
            $method .= '        return $this->call(\''.$reflectedMethod->getName().'\'';
            if (count($args)) {
                $method .= ', array(';
                foreach ($args as $rp) {
                    if ($rp->isPassedByReference()) {
                        $method .= '&';
                    }
                    $method .= '$';
                    $method .= $rp->getName();
                    if ($rp->getPosition() < count($reflectedMethod->getParameters()) - 1) {
                        $method .= ', ';
                    }
                }
                $method .= ')';
            }
            $method .= ");\n";
            $method .= "    }\n";

            $methods .= $method;
        }

        return $methods;
    }
}
