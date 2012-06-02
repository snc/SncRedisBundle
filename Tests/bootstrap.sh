#!/bin/sh
wget --quiet http://getcomposer.org/composer.phar && \

# see https://github.com/composer/composer/issues/640
sed -i -e 's#"symfony/dependency-injection": "2.0.*",#"symfony/dependency-injection": "2.0.*", "doctrine/common": "2.1.*",#' composer.json

php composer.phar install --dev && \
mkdir Snc && ln -s ../ Snc/RedisBundle
