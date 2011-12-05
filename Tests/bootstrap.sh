#!/bin/sh
wget --quiet http://getcomposer.org/composer.phar && \
php composer.phar install --install-suggests && \
mkdir Snc && ln -s ../ Snc/RedisBundle
