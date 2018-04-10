# Update notes #

## 3.0.0 ##

- Dropped support for Symfony 3.3 and below, as well as PHP 5.x

- Dropped official support for HHVM

- RedisSessionHandler is now implementing \SessionUpdateTimestampHandlerInterface
  by extending Symfony's AbstractSessionHandler. This requires Symfony 3.4 or above
  but brings session fixation protection as well as better performance as it avoids
  writing back the session data on every request. Session data is only written if 
  it changed.

- All services created by the bundle are now private as per Symfony recommended
  practices. Use Dependency Injection or Service Locators to access services.

## 2.0.0 ##

- Passwords in DSNs must now be properly urlencoded if they contain any of
  the following special characters: `@`, `%` or `:`. Encode them as `%40`,
  `%25` and `%3A` respectively. The `\@` notation for escaping `@` has been
  removed.

- The `Snc\RedisBundle\Monolog\Handler\RedisHandler` class has been removed
  in favor of using the native Monolog RedisHandler.

- The session keys changed from `$prefix:$key` to simply `$prefix$key`, no more
  `:` added in the middle, so add it to your prefix if you want one.

- The argument truncation limit in the logs was increased from 32 bytes to
  10KB.

## 1.0.11 and 1.1.6 ##

The monolog handler was renamed from `monolog.handler.redis` to
`snc_redis.monolog.handler`, you have to update your configuration.

Before:

``` yaml
monolog:
    handlers:
        main:
            type: service
            id: monolog.handler.redis
            level: debug
```

After:

``` yaml
monolog:
    handlers:
        main:
            type: service
            id: snc_redis.monolog.handler
            level: debug
```

## 1.1.0 ##

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

### 2012-02-18 ###

The `master` branch is now in sync with the symfony master branch.
Please use the `2.0` branch if you are working with Symfony 2.0.

### 2012-02-17 ###

The `RedisSessionStorage` class was refactored to reflect the changes
in the symfony master branch. The data is not saved in a HASH anymore
so keep in mind that your old sessions get lost.

### 2011-09-23 ###

If you want to use any of the doctrine caches, you now have to
configure the `entity_manager` and/or `document_manager` parameters.
Previously the bundle registered the caches for the `default` managers.

### 2011-07-01 ###

The `RedisBundle` is now vendor prefixed.
Please follow the following steps to update your Symfony project.

#### Update your kernel class ####

Replace `new Snc\RedisBundle\RedisBundle()` with `new Snc\RedisBundle\SncRedisBundle()`.

#### Update your config files ####

Replace `redis:` with `snc_redis:` in all of you `.yml` config files.

#### Update your code ####

All services are now prefixed by `snc_redis.` instead of `redis.`.
