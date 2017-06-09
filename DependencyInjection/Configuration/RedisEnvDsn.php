<?php

namespace Snc\RedisBundle\DependencyInjection\Configuration;

class RedisEnvDsn implements RedisDsnInterface
{
    /**
     * @var string
     */
    private $dsn;

    /**
     * @param string $dsn
     */
    public function __construct($dsn)
    {
        $this->dsn = $dsn;
    }

    /**
     * @return bool
     */
    public function isValid()
    {
        return (bool)preg_match('#^env_\w+_[0-9a-fA-F]{32}$#', $this->dsn);
    }

    /**
     * @return null
     */
    public function getAlias()
    {
        return null;
    }

    /**
     * @return string
     */
    public function getDsn()
    {
        return $this->dsn;
    }
}
