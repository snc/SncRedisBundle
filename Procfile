redis: redis-server .github/workflows/redis-configs/redis.conf --port 6379
redis-acl: redis-server .github/workflows/redis-configs/redis-acl.conf --port 7099
redis-sentinel: redis-server .github/workflows/redis-configs/redis-sentinel.conf --sentinel
redis-node1: sh -c 'cd .github/workflows/redis-configs/redis-node1 && redis-server redis.conf --port 7079'
redis-node2: sh -c 'cd .github/workflows/redis-configs/redis-node2 && redis-server redis.conf --port 7080'
redis-node3: sh -c 'cd .github/workflows/redis-configs/redis-node3 && redis-server redis.conf --port 7081'
redis-node4: sh -c 'cd .github/workflows/redis-configs/redis-node4 && redis-server redis.conf --port 7082'
redis-node5: sh -c 'cd .github/workflows/redis-configs/redis-node5 && redis-server redis.conf --port 7083'
redis-node6: sh -c 'cd .github/workflows/redis-configs/redis-node6 && redis-server redis.conf --port 7084'