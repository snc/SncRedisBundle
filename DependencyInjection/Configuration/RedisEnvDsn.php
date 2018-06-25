<?php

/*
 * This file is part of the SncRedisBundle package.
 *
 * (c) Henrik Westphal <henrik.westphal@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Snc\RedisBundle\DependencyInjection\Configuration;

class RedisEnvDsn
{
    private $dsn;

    public function __construct($dsn)
    {
        $this->dsn = $dsn;
    }

    public function __toString()
    {
        return $this->dsn;
    }
}
