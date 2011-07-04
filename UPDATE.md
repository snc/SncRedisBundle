## Update Notes ##

The `RedisBundle` is vendor prefixed since 2011-07-01.
Please follow the following steps to update your Symfony2 project.

### Update your kernel class ###

Replace `new Snc\RedisBundle\RedisBundle()` with `new Snc\RedisBundle\SncRedisBundle()`.

### Update your config files ###

Replace `redis:` with `snc_redis:` in all of you `.yml` config files.

### Update your code ###

All services are now prefixed by `snc_redis.` instead of `redis.`.
