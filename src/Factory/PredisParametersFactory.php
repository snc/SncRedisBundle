<?php

declare(strict_types=1);

namespace Snc\RedisBundle\Factory;

use InvalidArgumentException;
use Predis\Connection\ParametersInterface;
use Snc\RedisBundle\DependencyInjection\Configuration\RedisDsn;

use function array_merge;
use function is_a;
use function sprintf;

/** @internal */
class PredisParametersFactory
{
    /** @param array<string, mixed> $options */
    public static function create(array $options, string $class, string $dsn): ParametersInterface
    {
        if (!is_a($class, ParametersInterface::class, true)) {
            throw new InvalidArgumentException(sprintf('%s::%s requires $class argument to implement %s', self::class, __METHOD__, ParametersInterface::class));
        }

        $defaultOptions = ['timeout' => null]; // Allow to be consistent will old version of Predis where default timeout was 5
        $dsnOptions     = static::parseDsn(new RedisDsn($dsn));
        $dsnOptions     = array_merge($defaultOptions, $options, $dsnOptions);

        if (
            isset($dsnOptions['persistent'], $dsnOptions['database'])
            && $dsnOptions['persistent'] === true
            && (int) $dsnOptions['database'] !== 0
        ) {
            $dsnOptions['persistent'] = (int) $dsnOptions['database'];
        }

        return new $class($dsnOptions);
    }

    /** @return mixed[] */
    private static function parseDsn(RedisDsn $dsn): array
    {
        $options = [];
        if ($dsn->getSocket() !== null) {
            $options['scheme'] = 'unix';
            $options['path']   = $dsn->getSocket();
        } else {
            $options['scheme'] = $dsn->getTls() ? 'tls' : 'tcp';
            $options['host']   = $dsn->getHost();
            $options['port']   = $dsn->getPort();
            if ($dsn->getDatabase() !== null) {
                $options['path'] = $dsn->getDatabase();
            }
        }

        if ($dsn->getDatabase() !== null) {
            $options['database'] = $dsn->getDatabase();
        }

        $options['password'] = $dsn->getPassword();
        $options['weight']   = $dsn->getWeight();

        if ($dsn->getAlias() !== null) {
            $options['alias'] = $dsn->getAlias();
        }

        return $options;
    }
}
