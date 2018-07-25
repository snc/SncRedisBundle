<?php

namespace Snc\RedisBundle\Factory;

use Predis\Connection\ParametersInterface;
use Snc\RedisBundle\DependencyInjection\Configuration\RedisDsn;

class PredisParametersFactory
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
        if (!is_a($class, '\Predis\Connection\ParametersInterface', true)) {
            throw new \InvalidArgumentException(sprintf('%s::%s requires $class argument to implement %s', __CLASS__, __METHOD__, '\Predis\Connection\ParametersInterface'));
        }

        $dsnOptions = static::parseDsn(new RedisDsn($dsn));
        $dsnOptions = array_merge($options, $dsnOptions);

        return new $class($dsnOptions);
    }

    /**
     * @param RedisDsn $dsn
     *
     * @return array
     */
    private static function parseDsn(RedisDsn $dsn)
    {
        if (null !== $dsn->getSocket()) {
            $options['scheme'] = 'unix';
            $options['path'] = $dsn->getSocket();
        } else {
            $options['scheme'] = $dsn->getTls() ? 'tls' : 'tcp';
            $options['host'] = $dsn->getHost();
            $options['port'] = $dsn->getPort();
            if (null !== $dsn->getDatabase()) {
                $options['path'] = $dsn->getDatabase();
            }
        }
        if (null !== $dsn->getDatabase()) {
            $options['database'] = $dsn->getDatabase();
        }
        $options['password'] = $dsn->getPassword();
        $options['weight'] = $dsn->getWeight();

        return $options;
    }
}
