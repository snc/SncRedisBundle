# RedisBundle ![project status](http://stillmaintained.com/snc/SncRedisBundle.png) [![build status](https://secure.travis-ci.org/snc/SncRedisBundle.png?branch=master)](https://secure.travis-ci.org/snc/SncRedisBundle) #

**Caution:** You have to use the `2.0` branch of this bundle if you work with Symfony 2.0.

## About ##

The RedisBundle adds `redis` services to your project's service container using [Predis](http://github.com/nrk/predis).

## Installation ##

### Using composer ###

Add `"snc/redis-bundle": "dev-master"` to your `require` section in the `composer.json` file:

``` json
{
    "require": {
        "snc/redis-bundle": "dev-master"
    }
}
```

### Using the symfony-standard vendor script ###

Append the following lines to your `deps` file:

    [SncRedisBundle]
        git=git://github.com/snc/SncRedisBundle.git
        target=/bundles/Snc/RedisBundle
        version=origin/master

    [predis]
        git=git://github.com/nrk/predis.git
        version=origin/v0.7

then run the `./bin/vendors install` command.

Register the `Snc` and `Predis` namespace in your project's autoload script (app/autoload.php):

``` php
<?php
$loader->registerNamespaces(array(
    // ...
    'Snc'                            => __DIR__.'/../vendor/bundles',
    'Predis'                         => __DIR__.'/../vendor/predis/lib',
    // ...
));
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

Configure the `redis` service in your config:

``` yaml
snc_redis:
    clients:
        default:
            type: predis
            alias: default
            dsn: redis://localhost
```

You have to configure at least one client. In the above example your service
container will contain the service `snc_redis.default`.

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
            dsn: [ redis://localhost/3?weight=10, redis://localhost/4?weight=5, redis://localhost/5?weight=1 ]
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
            entity_manager: [default, read]  # you may also specify multiple entity_manager connections
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
            id: monolog.handler.redis
            level: debug
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

Please note that you don't have to configure the `swiftmailer.spool` property.

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
            dsn: [ redis://127.0.0.1/1, redis://127.0.0.2/2, redis://pw@/var/run/redis/redis-1.sock:63790/10, redis://pw@127.0.0.1:63790/10 ]
            options:
                profile: 2.4
                connection_timeout: 10
                connection_persistent: true
                read_write_timeout: 30
                iterable_multibulk: false
                throw_errors: true
                cluster: Snc\RedisBundle\Client\Predis\Network\PredisCluster
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
