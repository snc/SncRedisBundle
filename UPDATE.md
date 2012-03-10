# Update notes #

## 1.1.0 (2012-xx-xx) ##

The configuration syntax has been simplified. The `connections` setting was
merged into the `clients` setting.

Before:

``` yaml
snc_redis:
    connections:
        default:
            alias: default
            host: localhost
            port: 6379
            database: 0
    clients:
        default:
            alias: default
            connection: default
```

After:

``` yaml
snc_redis:
    clients:
        default:
            type: predis
            alias: default
            dsn: redis://localhost
```

The service names have been simplified, too. The above configuration will
register the service `snc_redis.default`. The old service names like
`snc_redis.default_client` are still available as an alias.

If you are using the Monolog or SwiftMailer features, then you have to
update your configuration by renaming the `connection` setting to `client`.

## Older notes ##

### 2011-09-23 ###

If you want to use any of the doctrine caches, you now have to
configure the `entity_manager` and/or `document_manager` parameters.
Previously the bundle registered the caches for the `default` managers.

### 2011-07-01 ###

The `RedisBundle` is now vendor prefixed.
Please follow the following steps to update your Symfony2 project.

#### Update your kernel class ####

Replace `new Snc\RedisBundle\RedisBundle()` with `new Snc\RedisBundle\SncRedisBundle()`.

#### Update your config files ####

Replace `redis:` with `snc_redis:` in all of you `.yml` config files.

#### Update your code ####

All services are now prefixed by `snc_redis.` instead of `redis.`.
