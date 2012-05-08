#!/bin/sh
wget --quiet http://getcomposer.org/composer.phar && \
php composer.phar install --dev && \
mkdir Snc && ln -s ../ Snc/RedisBundle
