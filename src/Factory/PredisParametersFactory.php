<?php

declare(strict_types=1);

namespace Snc\RedisBundle\Factory;

use InvalidArgumentException;
use Predis\Connection\ParametersInterface;
use Snc\RedisBundle\DependencyInjection\Configuration\RedisDsn;

use function array_filter;
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

        $defaultOptions = ['timeout' => null]; // Allow to be consistent with old version of Predis where default timeout was 5
        $dsnOptions     = static::parseDsn(new RedisDsn($dsn));
        $dsnOptions     = array_merge($defaultOptions, $options, $dsnOptions);

        if (!empty($dsnOptions['persistent']) && !empty($dsnOptions['database'])) {
            $dsnOptions['persistent'] = (int) $dsnOptions['database'];
        }

        return new $class($dsnOptions);
    }

    /** @return mixed[] */
    private static function parseDsn(RedisDsn $dsn): array
    {
        $socket  = $dsn->getSocket();
        $options = [
            'password' => $dsn->getPassword(),
            'weight' => $dsn->getWeight(),
        ];

        if ($socket !== null) {
            $options['scheme'] = 'unix';
            $options['path']   = $socket;
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

        if ($dsn->getAlias() !== null) {
            $options['alias'] = $dsn->getAlias();
        }

        if ($dsn->getRole() !== null) {
            $options['role'] = $dsn->getRole();
        }

        return array_filter($options, static fn ($value) => $value !== null);
    }
}
