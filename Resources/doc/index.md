# RedisBundle ![project status](http://stillmaintained.com/snc/SncRedisBundle.png) [![build status](https://secure.travis-ci.org/snc/SncRedisBundle.png?branch=master)](https://secure.travis-ci.org/snc/SncRedisBundle) #

## About ##

This bundle integrates [Predis](https://github.com/nrk/predis) and [phpredis](https://github.com/nicolasff/phpredis) into your Symfony2 application.

## Installation ##

Add the `snc/redis-bundle` package to your `require` section in the `composer.json` file.

``` bash
$ composer require snc/redis-bundle 1.1.x-dev
```

If you want to use the `predis` client library, you have to add the `predis/predis` package, too.

``` bash
$ composer require predis/predis 0.8.x-dev
```

Add the RedisBundle to your application's kernel:

``` php
<?php
public function registerBundles()
{
    $bundles = array(
        // ...
        new Snc\RedisBundle\SncRedisBundle(),
        // ...
    );
    ...
}
```

## Usage ##

Configure the `redis` client(s) in your `config.yml`:

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
            logging: %kernel.debug%
        cache:
            type: predis
            alias: cache
            dsn: redis://secret@localhost/1
            options:
                profile: 2.2
                connection_timeout: 10
                read_write_timeout: 30
        session:
            type: predis
            alias: session
            dsn: redis://localhost/2
        cluster:
            type: predis
            alias: cluster
            dsn:
                - redis://localhost/3?weight=10
                - redis://localhost/4?weight=5
                - redis://localhost/5?weight=1
```

In your controllers you can now access all your configured clients:

``` php
<?php
$redis = $this->container->get('snc_redis.default');
$val = $redis->incr('foo:bar');
$redis_cluster = $this->container->get('snc_redis.cluster');
$val = $redis_cluster->get('ab:cd');
$val = $redis_cluster->get('ef:gh');
$val = $redis_cluster->get('ij:kl');
```

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


### Sessions ###

Use Redis sessions by adding the following to your config:

``` yaml
snc_redis:
    ...
    session:
        client: session
```

This will use the default prefix `session`.

You may specify another `prefix`:

``` yaml
snc_redis:
    ...
    session:
        client: session
        prefix: foo
```

You can disable the automatic registration of the `session.storage` alias
by setting `use_as_default` to `false`:

``` yaml
snc_redis:
    ...
    session:
        client: session
        prefix: foo
        use_as_default: false
```

By default, a TTL is set using the `framework.session.cookie_lifetime` parameter. But
you can override it using the `ttl` option:

``` yaml
snc_redis:
    ...
    session:
        client: session
        ttl: 1200
```

This will make session data expire after 20 minutes, on the **server side**.
This is hightly recommended if you don't set an expiration date to the session
cookie. Note that using Redis for storing sessions is a good solution to avoid
garbage collection of sessions by PHP.

### Doctrine caching ###

Use Redis caching for Doctrine by adding this to your config:

``` yaml
snc_redis:
    ...
    doctrine:
        metadata_cache:
            client: cache
            entity_manager: default          # the name of your entity_manager connection
            document_manager: default        # the name of your document_manager connection
        result_cache:
            client: cache
            entity_manager: [default, read]  # you may specify multiple entity_managers
        query_cache:
            client: cache
            entity_manager: default
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

### SwiftMailer spooling ###

You can spool your mails in a redis `LIST` by adding this to your config:

``` yaml
snc_redis:
    clients:
        default:
            type: predis
            alias: default
            dsn: redis://localhost
            logging: false
    swiftmailer:
        client: default
        key: swiftmailer
```

Additionally you have to configure the swiftmailer spool:

Since version 2.2.6 and 2.3.4 of the SwiftmailerBundle you can configure
custom spool implementations using the `service` type:

``` yaml
swiftmailer:
    ...
    spool:
        type: service
        id: snc_redis.swiftmailer.spool
```

If you are using an older version of the SwiftmailerBundle the following configuration
should work, but this was kind of a hack:

``` yaml
swiftmailer:
    ...
    spool:
        type: redis
```


### Complete configuration example ###

``` yaml
snc_redis:
    clients:
        default:
            type: predis
            alias: default
            dsn: redis://localhost
            logging: %kernel.debug%
        cache:
            type: predis
            alias: cache
            dsn: redis://localhost/1
            logging: true
        cluster:
            type: predis
            alias: cluster
            dsn:
                - redis://127.0.0.1/1
                - redis://127.0.0.2/2
                - redis://pw@/var/run/redis/redis-1.sock/10
                - redis://pw@127.0.0.1:63790/10
            options:
                profile: 2.4
                connection_timeout: 10
                connection_persistent: true
                read_write_timeout: 30
                iterable_multibulk: false
                throw_errors: true
                cluster: Snc\RedisBundle\Client\Predis\Connection\PredisCluster
                replication: false
    session:
        client: default
        prefix: foo
        use_as_default: true
    doctrine:
        metadata_cache:
            client: cache
            entity_manager: default
            document_manager: default
        result_cache:
            client: cache
            entity_manager: [default, read]
            document_manager: [default, slave1, slave2]
            namespace: "dcrc:"
        query_cache:
            client: cache
            entity_manager: default
    monolog:
        client: cache
        key: monolog
    swiftmailer:
        client: default
        key: swiftmailer
```
