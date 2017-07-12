<?php

namespace Snc\RedisBundle\Factory;

use Predis\Connection\ParametersInterface;

class EnvParametersFactory
{
    /**
     * @param array $options
     * @param string $class
     * @param string $dsn
     *
     * @return ParametersInterface
     */
    public static function create($options, $class, $dsn)
    {
        $callable = array($class, 'parse');

        if(!is_callable($callable)) {
            $alias = isset($options['alias']) ? $options['alias'] : 'the client';

            throw new \InvalidArgumentException(sprintf('The parameters class you defined for %s does not support parsing url like DSNs.', $alias));
        }

        $dsnOptions = call_user_func($callable, $dsn);

        return new $class(array_merge($options, $dsnOptions));
    }
}
