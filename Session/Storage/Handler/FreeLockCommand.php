<?php

namespace Snc\RedisBundle\Session\Storage\Handler;

use Predis\Command\ScriptCommand;

/**
 * @deprecated Since 3.6
 */
class FreeLockCommand extends ScriptCommand
{
    public function getKeysCount()
    {
        return 1;
    }

    public function getScript()
    {
        return <<<LUA
if redis.call("GET", KEYS[1]) == ARGV[1] then
    return redis.call("DEL", KEYS[1])
else
    return 0
end
LUA;
    }
}
