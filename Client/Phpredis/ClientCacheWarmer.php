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

use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

/**
 * @author Javier Spagnoletti <phansys@gmail.com>
 */
class ClientCacheWarmer implements CacheWarmerInterface
{
    /**
     * @var ClientBuilder
     */
    private $clientBuilder;

    public function __construct(ClientBuilder $clientBuilder)
    {
        $this->clientBuilder = $clientBuilder;
    }

    /**
     * {@inheritdoc}
     */
    public function isOptional()
    {
        return !class_exists(\Redis::class) || class_exists(Client::class);
    }

    /**
     * {@inheritdoc}
     */
    public function warmUp($cacheDir)
    {
        if (!class_exists(\Redis::class)) {
            return;
        }

        $filename = $cacheDir.'/snc_phpredis_client.php';

        file_put_contents($filename, $this->clientBuilder->getClassContents());
        @chmod($filename, 0664);
    }
}
