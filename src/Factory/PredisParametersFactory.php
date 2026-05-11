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

use const STREAM_CRYPTO_METHOD_TLSv1_0_CLIENT;
use const STREAM_CRYPTO_METHOD_TLSv1_1_CLIENT;
use const STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT;

/** @internal */
class PredisParametersFactory
{
    /**
     * @param class-string<ParametersInterface> $class
     * @param array<string, mixed>              $options
     */
    public static function create(array $options, string $class, string $dsn): ParametersInterface
    {
        if (!is_a($class, ParametersInterface::class, true)) {
            throw new InvalidArgumentException(sprintf('%s::%s requires $class argument to implement %s', self::class, __METHOD__, ParametersInterface::class));
        }

        $defaultOptions = ['timeout' => null]; // Allow to be consistent with old version of Predis where default timeout was 5
        $dsnOptions     = static::parseDsn(new RedisDsn($dsn));

        // Merge ssl arrays so that DSN tls_version does not erase ssl_context from config
        $sslOptions = array_merge($options['ssl'] ?? [], $dsnOptions['ssl'] ?? []);
        $dsnOptions = array_merge($defaultOptions, $options, $dsnOptions);
        if ($sslOptions !== []) {
            $dsnOptions['ssl'] = $sslOptions;
        }

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
            'prefix' => $dsn->getPrefix(),
        ];

        if ($socket !== null) {
            $options['scheme'] = 'unix';
            $options['path']   = $socket;
        } else {
            $options['scheme'] = $dsn->getTls() ? 'tls' : 'tcp';
            $options['host']   = $dsn->getHost();
            $options['port']   = $dsn->getPort();

            if ($dsn->getTls() && $dsn->getTlsVersion() !== null) {
                $options['ssl'] = ['crypto_type' => self::tlsVersionToCryptoType($dsn->getTlsVersion())];
            }

            if ($dsn->getDatabase() !== null) {
                $options['path'] = $dsn->getDatabase();
            }
        }

        if ($dsn->getUsername() !== null) {
            $options['username'] = $dsn->getUsername();
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

    private static function tlsVersionToCryptoType(string $version): int
    {
        return match ($version) {
            '1.0' => STREAM_CRYPTO_METHOD_TLSv1_0_CLIENT,
            '1.1' => STREAM_CRYPTO_METHOD_TLSv1_1_CLIENT,
            '1.2' => STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT,
            default => throw new InvalidArgumentException(sprintf('Unsupported TLS version "%s". Supported versions: 1.0, 1.1, 1.2.', $version)),
        };
    }
}
