<?php

declare(strict_types=1);

namespace Snc\RedisBundle\Factory;

use InvalidArgumentException;
use Predis\Connection\ParametersInterface;
use Snc\RedisBundle\DependencyInjection\Configuration\RedisDsn;

use function array_filter;
use function array_map;
use function array_merge;
use function constant;
use function count;
use function defined;
use function is_a;
use function is_array;
use function is_string;
use function sprintf;
use function str_replace;

/** @internal */
class PredisParametersFactory
{
    /**
     * @param class-string<ParametersInterface>      $class
     * @param array<string, mixed>                   $options
     * @param string|list<string>|list<list<string>> $dsn
     *
     * @return ParametersInterface|list<ParametersInterface>
     */
    public static function create(array $options, string $class, string|array $dsn): ParametersInterface|array
    {
        if (is_string($dsn)) {
            $dsn = [$dsn];
        }

        // json:/csv: env processors can produce a single-element array wrapping the actual list
        if (count($dsn) === 1 && is_array($dsn[0])) {
            $dsn = $dsn[0];
        }

        $parameters = array_map(
            static fn (string $d) => static::createFromSingleDsn($options, $class, $d),
            $dsn,
        );

        return count($parameters) === 1 ? $parameters[0] : $parameters;
    }

    /**
     * @param class-string<ParametersInterface> $class
     * @param array<string, mixed>              $options
     */
    private static function createFromSingleDsn(array $options, string $class, string $dsn): ParametersInterface
    {
        if (!is_a($class, ParametersInterface::class, true)) {
            throw new InvalidArgumentException(sprintf('%s::%s requires $class argument to implement %s', self::class, __METHOD__, ParametersInterface::class));
        }

        $defaultOptions = ['timeout' => null]; // Allow to be consistent with old version of Predis where default timeout was 5
        $dsnOptions     = static::parseDsn(new RedisDsn($dsn));
        $dsnOptions     = array_merge($defaultOptions, $options, $dsnOptions);
        $ssl            = array_merge($options['ssl'] ?? [], $dsnOptions['ssl'] ?? []);
        if ($ssl !== []) {
            $dsnOptions['ssl'] = $ssl;
        } else {
            unset($dsnOptions['ssl']);
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
        $constant = sprintf('STREAM_CRYPTO_METHOD_TLSv%s_CLIENT', str_replace('.', '_', $version));

        if (!defined($constant)) {
            throw new InvalidArgumentException(sprintf('Unsupported TLS version "%s".', $version));
        }

        return constant($constant);
    }
}
