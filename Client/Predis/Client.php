<?php

namespace Snc\RedisBundle\Client\Predis;

use Predis\Network\ConnectionCluster;
use Predis\Network\IConnectionSingle;

/**
 * Client
 */
class Client extends \Predis\Client
{
    /**
     * {@inheritdoc}
     */
    public function __construct($parameters = null, $options = null)
    {
        parent::__construct($parameters, $options);
        $connection = $this->getConnection();
        if ($connection instanceof ConnectionCluster) {
            foreach ($connection as $conn) {
                $this->pushInitCommands($conn);
            }
        } else {
            $this->pushInitCommands($connection);
        }
    }

    /**
     * Calls session_write_close() to fix bug a bug in PHP < 5.3.3 (caused by APC)
     * where object destruction occurs in the wrong order.
     *
     * @see http://pecl.php.net/bugs/bug.php?id=16745
     */
    public function __destruct()
    {
        session_write_close();
    }

    /**
     * Pushes required initialization commands
     *
     * @param \Predis\Network\IConnectionSingle $connection An IConnectionSingle instance
     */
    private function pushInitCommands(IConnectionSingle $connection)
    {
        $params = $connection->getParameters();
        if (null !== $params->password) {
            $connection->pushInitCommand($this->createCommand('auth', array($params->password)));
        }
        if (null !== $params->database) {
            $connection->pushInitCommand($this->createCommand('select', array($params->database)));
        }
    }
}
