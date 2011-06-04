# RedisBundle ![project status](http://stillmaintained.com/snc/RedisBundle.png) #

## About ##

The RedisBundle adds `redis` services to your project's service container using [Predis](http://github.com/nrk/predis).

## Installation ##

Put the RedisBundle into the src/Snc dir:

    $ git clone git://github.com/snc/RedisBundle.git src/Snc/RedisBundle

or as a submodule:

    $ git submodule add git://github.com/snc/RedisBundle.git src/Snc/RedisBundle

Put the [Predis](http://github.com/nrk/predis) library into the vendor dir:

    $ git clone git://github.com/nrk/predis.git vendor/predis

or as a submodule:

    $ git submodule add git://github.com/nrk/predis.git vendor/predis

Register the `Snc` and `Predis` namespace in your project's autoload script (app/autoload.php):

``` php
<?php
$loader->registerNamespaces(array(
    // ...
    'Snc'                            => __DIR__.'/../src',
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
        new Snc\RedisBundle\RedisBundle(),
        // ...
    );
    ...
}
```

## Usage ##

Configure the `redis` service in your config:

``` yaml
redis:
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

You have to configure at least one connection and one client. In the above
example your service container will contain the service `redis.default_client`.

A more complex setup which contains a clustered client could look like this:

``` yaml
redis:
    connections:
        default:
            alias: default
            host: localhost
            port: 6379
            database: 0
            logging: %kernel.debug%
        cache:
            alias: cache
            host: localhost
            port: 6379
            database: 1
            password: secret
            connection_timeout: 10
            read_write_timeout: 30
        session:
            alias: session
            host: localhost
            port: 6379
            database: 2
        cluster1:
            alias: cluster1
            host: localhost
            port: 6379
            database: 3
            weight: 10
        cluster2:
            alias: cluster2
            host: localhost
            port: 6379
            database: 4
            weight: 5
        cluster3:
            alias: cluster3
            host: localhost
            port: 6379
            database: 5
            weight: 1
    clients:
        default:
            alias: default
            connection: default
        cache:
            alias: cache
            connection: cache
            options:
                profile: 2.2
        session:
            alias: session
            connection: session
        cluster:
            alias: cluster
            connection: [ cluster1, cluster2, cluster3 ]
```

In your controllers you can now access all your configured clients:

``` php
<?php
$redis = $this->container->get('redis.default_client');
$val = $redis->incr('foo:bar');
$redis_cluster = $this->container->get('redis.cluster_client');
$val = $redis_cluster->get('ab:cd');
$val = $redis_cluster->get('ef:gh');
$val = $redis_cluster->get('ij:kl');
```

### Sessions ###

Use Redis sessions by adding the following to your config:

``` yaml
redis:
    ...
    session:
        client: session
```

This will use the default prefix `session`.

You may specify another `prefix`:

``` yaml
redis:
    ...
    session:
        client: session
        prefix: foo
```

### Doctrine caching ###

Use Redis caching for Doctrine by adding this to your config:

``` yaml
redis:
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
```

If you don't specify an `entity_manager` connection name then the `default` one will be used.

### Complete configuration example ###

``` yaml
redis:
    connections:
        default:
            alias: default
            host: localhost
            port: 6379
            database: 0
            logging: %kernel.debug%
        cache:
            alias: cache
            host: localhost
            port: 6379
            database: 1
            password: secret
            connection_timeout: 10
            read_write_timeout: 30
            iterable_multibulk: false
            throw_errors: true
        session:
            alias: session
            host: localhost
            port: 6379
            database: 2
        cluster1:
            alias: cluster1
            host: localhost
            port: 6379
            database: 3
            weight: 10
        cluster2:
            alias: cluster2
            host: localhost
            port: 6379
            database: 4
            weight: 5
        cluster3:
            alias: cluster3
            host: localhost
            port: 6379
            database: 5
            weight: 1
        socket:
            alias: socket
            scheme: unix
            path: /tmp/socket.sock
            database: 0
            logging: true
    clients:
        default:
            alias: default
            connection: default
            options:
                profile: 2.0
        cache:
            alias: cache
            connection: cache
            options:
                profile: 2.2
        session:
            alias: session
            connection: session
            options:
                profile: 1.2
        cluster:
            alias: cluster
            connection: [ cluster1, cluster2, cluster3 ]
            options:
                profile: DEV
                cluster: Snc\RedisBundle\Client\Predis\Network\PredisCluster
        socket:
            alias: socket
            connection: socket
    session:
        client: session
        prefix: foo
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
```
