# Upgrade notes #

## 4.0.0 ##

- Removed redis profiler storage feature `snc_redis.profiler_storage`
- Removed SwiftMailer integration
- Removed session integration. [Follow official Symfony guide](https://symfony.com/doc/current/session/database.html#store-sessions-in-a-key-value-database-redis) instead
- Removed RateLimit class. Use symfony/rate-limiter instead
- Removed doctrine integration. Set up your cache pools via framework.yaml and follow doctrine-bundle documentation to configure Doctrine to use them.
- `class.phpredis_connection_wrapper` and `class.phpredis_clusterclient_connection_wrapper` config options have been removed 
- Added requirement for `ocramius/proxy-manager` or `friendsofphp/proxy-manager-lts` if logging is enabled for phpredis client
- Increased PHP requirement from 7.2 to 7.4
- Added return and parameter type declarations to all functions
- Removed `redis:flushdb` command. Use `redis:query flushdb` instead
- Removed `redis:flushall` command. Use `redis:query flushall` instead
- Removed `Snc\RedisBundle\Command\RedisBaseCommand` class and `snc_redis.command` DI tag