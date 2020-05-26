<?php
declare(strict_types=1);

namespace Snc\RedisBundle\Client\Phpredis;

/**
 * @internal
 *
 * Utility class for PHP Redis tasks
 *
 * @author Ole Rößner <oroessner@gmail.com>
 */
final class Phpredis
{
    /**
     * Older version of phpredis extension do not support lazy loading
     */
    private const MINIMUM_LAZY_VERSION = '4.1.1';

    /** @var string|null redis extension version */
    private $version;

    private $supportsLazy;

    public function supportsLazyServices(): bool
    {
        if (null === $this->supportsLazy) {
            $this->supportsLazy = $this->versionIsGreaterOrEqual(self::MINIMUM_LAZY_VERSION);
            if (false === $this->supportsLazy) {
                @trigger_error(
                  sprintf('Lazy loading Redis is not supported on PhpRedis %s. Please update to PhpRedis %s or higher.',
                    $this->getVersion(), self::MINIMUM_LAZY_VERSION),
                  E_USER_WARNING
                );
            }
        }

        return $this->supportsLazy;
    }

    public function versionIsGreaterOrEqual(string $version): bool
    {
        return version_compare($this->getVersion(), $version, '>=');
    }

    public function getVersion(): string
    {
        if (!$this->version) {
            $this->version = phpversion('redis');
        }
        return $this->version;
    }
}
