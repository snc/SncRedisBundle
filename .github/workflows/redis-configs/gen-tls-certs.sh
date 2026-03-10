#!/usr/bin/env sh
set -e
mkdir -p /tmp/redis-tls
if [ ! -f /tmp/redis-tls/server.crt ]; then
    openssl req -x509 -newkey rsa:2048 \
        -keyout /tmp/redis-tls/server.key \
        -out /tmp/redis-tls/server.crt \
        -days 3650 -nodes \
        -subj "/CN=localhost" \
        2>/dev/null
    cp /tmp/redis-tls/server.crt /tmp/redis-tls/ca.crt
fi
