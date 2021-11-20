# Update notes #

## 4.0.0 ##

- Removed redis profiler storage feature `snc_redis.profiler_storage`
- Removed SwiftMailer integration
- Removed session integration. [Follow official Symfony guide](https://symfony.com/doc/current/session/database.html#store-sessions-in-a-key-value-database-redis) instead
- Removed RateLimit class. Use symfony/rate-limiter instead
- Removed doctrine integration. Set up your cache pools via framework.yaml and follow doctrine-bundle documentation to configure Doctrine to use them.
