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

    redis.config:
        connections:
            default:
                host: localhost
                port: 6379
                database: 0
        clients:
            default: ~

You have to configure at least one connection and one client. In the above
example your service container will contain the service `redis.default_client`.

If you don't specify a connection for the client as above, the client will
look for a connection with the same alias. The following example is the same
as above:

    redis.config:
        connections:
            default:
                host: localhost
                port: 6379
                database: 0
        clients:
            default:
                connection: default

A more complex setup which contains a clustered client could look like this:

    redis.config:
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
            default: ~
            cache:
                connection: cache
            session: ~
            cluster:
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

    redis.session: ~

This will use the default client `session` with the default prefix `session`.

You may specify another `client` and `prefix` when storing session data.

    redis.session:
        client: session
        prefix: foo

### Doctrine caching ###

Use Redis caching for Doctrine by adding this to your config:

    redis.doctrine:
        client: cache
        metadata_cache:  default           # <-- the name of your entity_manager connection
        result_cache:    [default, read]   # you may also specify multiple entity_manager connections
        query_cache:     default

If you omit the `client` setting then the bundle will use the client named `cache`.
