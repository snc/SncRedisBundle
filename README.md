# RedisBundle #

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

Register the `Snc` namespace in your project's autoload script (app/autoload.php):

    $loader->registerNamespaces(array(
        ...
        'Snc'                            => __DIR__.'/../src',
        ...
    ));

Add the [Predis](http://github.com/nrk/predis) autoloading to your project's autoload script (app/autoload.php):

    spl_autoload_register(function($class)
    {
        if (strpos($class, 'Predis\\') === 0) {
            require_once __DIR__.'/../vendor/predis/lib/Predis.php';
            return true;
        }
    });

Add the RedisBundle to your application's kernel:

    public function registerBundles()
    {
        $bundles = array(
            ...
            new Snc\RedisBundle\RedisBundle(),
            ...
        );
        ...
    }


## Usage ##

Configure the `redis` service in your config:

    redis:
        connections:
            default:
                host: localhost
                port: 6379
                database: 0
        clients:
            default:
                alias: default
                connection: default

You have to configure at least one connection and one client. In the above
example your service container will contain the service `redis.default_client`.

A more complex setup which contains a clustered client could look like this:

    redis:
        connections:
            default:
                host: localhost
                port: 6379
                database: 0
                logging: %kernel.debug%
            cache:
                host: localhost
                port: 6379
                database: 1
                password: secret
                connection_timeout: 10
                read_write_timeout: 30
            session:
                host: localhost
                port: 6379
                database: 2
            cluster1:
                host: localhost
                port: 6379
                database: 3
                weight: 10
            cluster2:
                host: localhost
                port: 6379
                database: 4
                weight: 5
            cluster3:
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
            session:
                alias: session
                connection: session
            cluster:
                alias: cluster
                connection: [ cluster1, cluster2, cluster3 ]

In your controllers you can now access all your configured clients:

    $redis = $this->container->get('redis.default_client');
    $val = $redis->incr('foo:bar');
    $redis_cluster = $this->container->get('redis.cluster_client');
    $val = $redis_cluster->get('ab:cd');
    $val = $redis_cluster->get('ef:gh');
    $val = $redis_cluster->get('ij:kl');

### Sessions ###

Use Redis sessions by adding the following to your config:

    redis:
        ...
        session:
            client: session

This will use the default prefix `session`.

You may specify another `prefix`:

    redis:
        ...
        session:
            client: session
            prefix: foo

### Doctrine caching ###

Use Redis caching for Doctrine by adding this to your config:

    redis:
        ...
        doctrine:
            metadata_cache:
                client: cache
                entity_manager: default          # the name of your entity_manager connection
            result_cache:
                client: cache
                entity_manager: [default, read]  # you may also specify multiple entity_manager connections
            query_cache:
                client: cache

If you don't specify an `entity_manager` connection name then the `default` one will be used.

### Complete configuration example ###

    redis:
        connections:
            default:
                host: localhost
                port: 6379
                database: 0
                logging: %kernel.debug%
            cache:
                host: localhost
                port: 6379
                database: 1
                password: secret
                connection_timeout: 10
                read_write_timeout: 30
            session:
                host: localhost
                port: 6379
                database: 2
            cluster1:
                host: localhost
                port: 6379
                database: 3
                weight: 10
            cluster2:
                host: localhost
                port: 6379
                database: 4
                weight: 5
            cluster3:
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
            session:
                alias: session
                connection: session
            cluster:
                alias: cluster
                connection: [ cluster1, cluster2, cluster3 ]
        session:
            client: session
            prefix: foo
        doctrine:
            metadata_cache:
                client: cache
                entity_manager: default
            result_cache:
                client: cache
                entity_manager: [default, read]
            query_cache:
                client: cache
