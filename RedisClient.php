<?php

namespace Bundle\RedisBundle;

use \Predis\Client;


class RedisClient extends Client
{

	/**
     * Putting session_write_close() to fix bug caused by APC where object destruction occurs in the wrong order
     * Can be removed when we upgrade to >= PHP 5.3.3
     *
     * @see http://pecl.php.net/bugs/bug.php?id=16745
     */
	public function __destruct()
	{
		session_write_close();
	}
	
}