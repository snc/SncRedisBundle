# RedisBundle #
[![Latest Stable Version](https://poser.pugx.org/snc/redis-bundle/v/stable?format=flat-square)](https://packagist.org/packages/snc/redis-bundle)
[![Total Downloads](https://poser.pugx.org/snc/redis-bundle/downloads?format=flat-square)](https://packagist.org/packages/snc/redis-bundle)
[![Latest Stable Version](https://poser.pugx.org/snc/redis-bundle/v?format=flat-square)](https://packagist.org/packages/snc/redis-bundle)
[![License](https://poser.pugx.org/snc/redis-bundle/license?format=flat-square)](https://packagist.org/packages/snc/redis-bundle)
[![Monthly Downloads](https://poser.pugx.org/snc/redis-bundle/d/monthly?format=flat-square)](https://packagist.org/packages/snc/redis-bundle)
[![Daily Downloads](https://poser.pugx.org/snc/redis-bundle/d/daily?format=flat-square)](https://packagist.org/packages/snc/redis-bundle)
[![Build Status](https://github.com/snc/SncRedisBundle/actions/workflows/continuous-integration.yml/badge.svg)](https://github.com/snc/SncRedisBundle/actions)

## About ##

This bundle integrates [Predis](https://github.com/nrk/predis) and [PhpRedis](https://github.com/nicolasff/phpredis) into your Symfony 3.4+ application,
providing a fast and convenient interface to [Redis](https://redis.io/).

Using the native PhpRedis extension is recommended as it is faster and our main development platform. If the extension is not available and cannot
be installed in your environment Predis is considered a safe and portable alternative, and our integration should be functionally identical.

## Installation ##

Use [Composer](https://github.com/composer/composer):
```sh
composer require snc/redis-bundle
```

## Documentation ##

[Read the documentation in Resources/doc/](Resources/doc/index.md)

## License ##

See [LICENSE](LICENSE).
