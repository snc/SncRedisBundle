# RedisBundle #

## About ##

The RedisBundle adds a `redis` service to your project's service container using [Predis](http://github.com/nrk/predis).

## Installation ##

Put the RedisBundle into the src/Bundle dir:

    $ git clone git://github.com/snc/RedisBundle.git src/Bundle/RedisBundle

or as a submodule:

    $ git submodule add git://github.com/snc/RedisBundle.git src/Bundle/RedisBundle

Put the [Predis](http://github.com/nrk/predis) library into the src/vendor dir:

    $ git clone git://github.com/nrk/predis.git src/vendor/predis

or as a submodule:

    $ git submodule add git://github.com/nrk/predis.git src/vendor/predis

Add the [Predis](http://github.com/nrk/predis) autoloading to your project's bootstrap script (src/autoload.php):

    spl_autoload_register(function($class) use ($vendorDir)
    {
      if (strpos($class, 'Predis\\') === 0) {
          require_once $vendorDir.'/predis/lib/Predis.php';
          return true;
      }
    });

Add the RedisBundle to your application's kernel:

    public function registerBundles()
    {
        $bundles = array(
            ...
            new Bundle\RedisBundle\RedisBundle(),
            ...
        );
        ...
    }

Configure the `redis` service in your config:

    redis.config:
      host: localhost
      port: 6379
      database: 0
