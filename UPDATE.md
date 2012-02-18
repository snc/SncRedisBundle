# Update Notes #

## 2012-02-18 ##

The `master` branch is now in sync with the symfony master branch.
Please use the `2.0` branch if you are working with Symfony 2.0.

## 2012-02-17 ##

The `RedisSessionStorage` class was refactored to reflect the changes
in the symfony master branch. The data is not saved in a HASH anymore
so keep in mind that your old sessions get lost.

## 2011-09-23 ##

If you want to use any of the doctrine caches, you now have to
configure the `entity_manager` and/or `document_manager` parameters.
Previously the bundle registered the caches for the `default` managers.

## 2011-07-01 ##

The `RedisBundle` is now vendor prefixed.
Please follow the following steps to update your Symfony2 project.

### Update your kernel class ###

Replace `new Snc\RedisBundle\RedisBundle()` with `new Snc\RedisBundle\SncRedisBundle()`.

### Update your config files ###

Replace `redis:` with `snc_redis:` in all of you `.yml` config files.

### Update your code ###

All services are now prefixed by `snc_redis.` instead of `redis.`.
