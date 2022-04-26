# RedisBundle

## About ##

This bundle integrates [Predis](https://github.com/nrk/predis) and [phpredis](https://github.com/nicolasff/phpredis) into your Symfony application.

## Installation ##

Add the `snc/redis-bundle` package to your `require` section in the `composer.json` file.

``` bash
$ composer require snc/redis-bundle 2.x-dev
```

If you want to use the `predis` client library, you have to add the `predis/predis` package, too.

``` bash
$ composer require predis/predis
```

Add the RedisBundle to your application's kernel:

``` php
<?php
public function registerBundles()
{
    $bundles = [
        // ...
        new Snc\RedisBundle\SncRedisBundle(),
        // ...
    ];
    ...
}
```

## Usage ##

Configure the `redis` client(s) in your `config.yml`:

_Please note that passwords with special characters in the DSN string such as `@ % : +` must be urlencoded._

``` yaml
snc_redis:
    clients:
        default:
            type: predis
            alias: default
            dsn: redis://localhost
```

You have to configure at least one client. In the above example your service
container will contain the service `snc_redis.default` which will return a
`Predis` client.

Available types are `predis` and `phpredis`.

A more complex setup which contains a clustered client could look like this:

``` yaml
snc_redis:
    clients:
        default:
            type: predis
            alias: default
            dsn: redis://localhost
            logging: '%kernel.debug%'
        cache:
            type: predis
            alias: cache
            dsn: redis://secret@localhost/1
            options:
                profile: 2.2
                connection_timeout: 10
                read_write_timeout: 30
        cluster:
            type: predis
            alias: cluster
            dsn:
                - redis://localhost/3?weight=10
                - redis://localhost/4?weight=5
                - redis://localhost/5?weight=1
```

In your code you can now access all your configured clients using dependency
injection or service locators. The services are named `snc_redis.` followed by
the alias name, ie. `snc_redis.default` or `snc_redis.cluster` in the example
above.

A setup using `predis` master-slave replication could look like this:

``` yaml
snc_redis:
    clients:
        default:
            type: predis
            alias: default
            dsn:
                - redis://master-host?alias=master
                - redis://slave-host1
                - redis://slave-host2
            options:
                replication: true
```

Please note that the master dsn connection needs to be tagged with the ```master``` alias.
If not, `predis` will complain.

A setup using `predis` sentinel replication could look like this:

``` yaml
snc_redis:
    clients:
        default:
            type: predis
            alias: default
            dsn:
                - redis://localhost:26379
                - redis://otherhost:26379
            options:
                replication: sentinel
                service: mymaster
                parameters:
                    database: 1
                    password: pass
```

The `service` is the name of the set of Redis instances.
The optional parameters option can be used to set parameters like the 
database number and password for the master/slave connections, 
they don't apply for the connection to sentinel.
If you use a password, it must be in the password parameter and must
be omitted from the DSNs. Also make sure to use the sentinel port number
(26379 by default) in the DSNs, and not the default Redis port.
You can find more information about this on [Configuring Sentinel](https://redis.io/topics/sentinel#configuring-sentinel).

A setup using `RedisCluster` from `phpredis`  could look like this:

``` yaml
snc_redis:
    clients:
        default:
            type: phpredis
            alias: default
            dsn:
                - redis://localhost:7000
                - redis://localhost:7001
                - redis://localhost:7002
            options:
                cluster: true
```

#### Authentication using Redis ACL

Starting with redis 6.0, it is possible to use an [ACL](https://redis.io/docs/manual/security/acl/) system that only allows users with valid username and password to log in.
Using the `phpredis` driver, you can set up an authenticated connection like this:

``` yaml
snc_redis:
    clients:
        default:
            type: phpredis
            alias: default
            dsn: redis://localhost
            # dsn: redis://my_username:my_password@localhost <- username and password can be also set here
            options:
                parameters:
                    username: my_userame
                    password: my_password
                
```


### Sessions ###

Use Redis sessions by utilizing Symfony built-in Redis session handler like so:

First, define your redis clients:
``` yaml
snc_redis:
    clients:
        session:
            type: predis
            alias: session
            dsn: redis://localhost/1
```
Then, reference it in your framework.yaml config:
``` yaml
framework:
    ...
    session:
        handler_id: Symfony\Component\HttpFoundation\Session\Storage\Handler\RedisSessionHandler
services:
    Symfony\Component\HttpFoundation\Session\Storage\Handler\RedisSessionHandler:
        arguments: ['@snc_redis.session']
```

### Monolog logging ###

You can store your logs in a redis `LIST` by adding this to your config:

``` yaml
snc_redis:
    clients:
        monolog:
            type: predis
            alias: monolog
            dsn: redis://localhost/1
            logging: false
            options:
                connection_persistent: true
    monolog:
        client: monolog
        key: monolog

monolog:
    handlers:
        main:
            type: service
            id: snc_redis.monolog.handler
            level: debug
```

You can also add a custom formatter to the monolog handler

``` yaml
snc_redis:
    clients:
        monolog:
            type: predis
            alias: monolog
            dsn: redis://localhost/1
            logging: false
            options:
                connection_persistent: true
    monolog:
        client: monolog
        key: monolog
        formatter: my_custom_formatter
```

### Symfony Cache Pools ###

If you want to use one of the client connections for the Symfony App Cache or a Symfony Cache Pool, just use its service name as a cache pool provider:

```yaml
framework:
    cache:
        app: cache.adapter.redis
        # app cache from client config as default adapter/provider
        default_redis_provider: snc_redis.default
        pools:
            some-pool.cache:
                adapter: cache.adapter.redis
                # a specific provider, e.g. if you have a snc_redis.clients.cache
                provider: snc_redis.cache
```

### Profiler storage ###

:warning: this feature is not supported anymore since Symfony 4.4 and will be automatically disabled if you are using Symfony 4.4.

>As the profiler must only be used on non-production servers, the file storage is more than enough and no other implementations will ever be supported. 

### Complete configuration example ###

``` yaml
snc_redis:
    clients:
        default:
            type: predis
            alias: default
            dsn: redis://localhost
            logging: '%kernel.debug%'
        cache:
            type: predis
            alias: cache
            dsn: redis://localhost/1
            logging: false
        cluster:
            type: predis
            alias: cluster
            dsn:
                - redis://127.0.0.1/1
                - redis://127.0.0.2/2
                - redis://pw@/var/run/redis/redis-1.sock/10
                - redis://pw@127.0.0.1:63790/10
            options:
                prefix: foo
                profile: 2.4
                connection_timeout: 10
                connection_persistent: true
                read_write_timeout: 30
                iterable_multibulk: false
                throw_errors: true
                cluster: predis
    monolog:
        client: cache
        key: monolog
```

## Usage with `symfony/web-profiler-bundle`

If you are using [`symfony/web-profiler-bundle`](https://github.com/symfony/web-profiler-bundle)
and want to inspect commands sent by a configured Redis client, logging needs to be enabled for that client.

``` yaml
snc_redis:
    clients:
        default:
            type: predis
            alias: default
            dsn: redis://localhost/
            logging: '%kernel.debug%'
```

## Troubleshooting

If cache warmup fails for prod because a redis server is not available,
try to install [`symfony/proxy-manager-bridge`](https://symfony.com/doc/master/service_container/lazy_services.html):

``` bash
$ composer require symfony/proxy-manager-bridge
```

Once done some services will be lazy-loaded and could prevent unwanted connection call. 
