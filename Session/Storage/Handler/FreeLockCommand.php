<?php

namespace Snc\RedisBundle\Session\Storage\Handler;

use Predis\Command\ScriptCommand;

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
