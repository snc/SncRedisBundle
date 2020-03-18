<?php

namespace Snc\RedisBundle\Factory;

use Predis\Connection\ParametersInterface;
use Snc\RedisBundle\DependencyInjection\Configuration\RedisDsn;

/**
 * @internal
 */
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
        if (!is_a($class, ParametersInterface::class, true)) {
            throw new \InvalidArgumentException(sprintf('%s::%s requires $class argument to implement %s', __CLASS__, __METHOD__, ParametersInterface::class));
        }

        $defaultOptions = ['timeout' => null]; // Allow to be consistent will old version of Predis where default timeout was 5
        $dsnOptions = static::parseDsn(new RedisDsn($dsn));
        $dsnOptions = array_merge($defaultOptions, $options, $dsnOptions);

        if (isset($dsnOptions['persistent'], $dsnOptions['database'])
            && true === $dsnOptions['persistent']
            && (int)$dsnOptions['database'] !== 0
        ) {
            $dsnOptions['persistent'] = (int)$dsnOptions['database'];
        }

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

        if (null !== $dsn->getAlias()) {
            $options['alias'] = $dsn->getAlias();
        }

        return $options;
    }
}
