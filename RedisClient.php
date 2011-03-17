<?php

namespace Snc\RedisBundle;

class RedisClient extends \Predis\Client
{
    public function __construct($parameters = null, $options = null) {
        parent::__construct($parameters, $options);

        if($parameters instanceof IConnectionSingle)
        {
            $this->pushInitCommands($parameters);
        }
    }

    private function pushInitCommands(IConnectionSingle $connection) {
        $params = $connection->getParameters();
        if (isset($params->password)) {
            $connection->pushInitCommand($this->createCommand(
                'auth', array($params->password)
            ));
        }
        if (isset($params->database)) {
            $connection->pushInitCommand($this->createCommand(
                'select', array($params->database)
            ));
        }
    }

    /**
     * Putting session_write_close() to fix bug caused by APC where object 
     * destruction occurs in the wrong order
     * Can be removed when we upgrade to >= PHP 5.3.3
     *
     * @see http://pecl.php.net/bugs/bug.php?id=16745
     */
    public function __destruct()
    {
        session_write_close();
    }
}
