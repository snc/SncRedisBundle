<?php

namespace Snc\RedisBundle\Factory;

use InvalidArgumentException;
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
            throw new InvalidArgumentException(
                sprintf(
                    '%s::%s requires $class argument to implement %s',
                    __CLASS__,
                    __METHOD__,
                    '\Predis\Connection\ParametersInterface'
                )
            );
        }

        $finalOptions = array_merge(
            // Allow to be consistent with old version of Predis where default timeout was 5
            array('timeout' => null),
            $options,
            static::parseDsn(new RedisDsn($dsn))
        );

        if (isset($finalOptions['persistent'], $finalOptions['database']) && true === $finalOptions['persistent']) {
            $finalOptions['persistent'] = (int)$finalOptions['database'];
        }

        if (
            !isset($finalOptions['password'])
            && !isset($finalOptions['replication'])
            && isset($finalOptions['parameters']['password'])
        ) {
            $finalOptions['password'] = $finalOptions['parameters']['password'];
        }

        return new $class($finalOptions);
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

        if (null !== $dsn->getAlias()) {
            $options['alias'] = $dsn->getAlias();
        }

        return $options;
    }
}
