<?php

$class = <<<'EOF'
<?php

/*
 * This file is part of the SncRedisBundle package.
 *
 * (c) Henrik Westphal <henrik.westphal@gmail.com>
 * (c) Yassine Khial <yassine.khial@blablacar.com>
 * (c) Pierre Boudelle <pierre.boudelle@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Snc\RedisBundle\Client\Phpredis;

use Redis;
use Snc\RedisBundle\Logger\RedisLogger;

/**
 * PHP Redis client with logger.
 *
 * @author Henrik Westphal <henrik.westphal@gmail.com>
 * @author Yassine Khial <yassine.khial@blablacar.com>
 * @author Pierre Boudelle <pierre.boudelle@gmail.com>
 */
class {{classname}} extends Redis
{
    /**
     * @var RedisLogger
     */
    protected $logger;

    /**
     * @var string
     */
    protected $alias;

    /**
     * Constructor.
     *
     * @param array       $parameters List of parameters (only `alias` key is handled)
     * @param RedisLogger $logger     A RedisLogger instance
     */
    public function __construct(array $parameters = array(), RedisLogger $logger = null)
    {
        $this->logger = $logger;
        $this->alias = isset($parameters['alias']) ? $parameters['alias'] : '';
    }

    /**
     * Proxy function.
     *
     * @param string $name      A command name
     * @param array  $arguments Lit of command arguments
     *
     * @throws \RuntimeException If no Redis instance is defined
     *
     * @return mixed
     */
    private function call($name, array $arguments = array())
    {
        $startTime = microtime(true);
        $result = call_user_func_array("parent::$name", $arguments);
        $duration = (microtime(true) - $startTime) * 1000;

        if (null !== $this->logger) {
            $this->logger->logCommand($this->getCommandString($name, $arguments), $duration, $this->alias, false);
        }

        return $result;
    }

    /**
     * Returns a string representation of the given command including arguments.
     *
     * @param string $command   A command name
     * @param array  $arguments List of command arguments
     *
     * @return string
     */
    private function getCommandString($command, array $arguments)
    {
        $list = array();
        $this->flatten($arguments, $list);

        return trim(strtoupper($command).' '.implode(' ', $list));
    }

    /**
     * Flatten arguments to single dimension array.
     *
     * @param array $arguments An array of command arguments
     * @param array $list      Holder of results
     */
    private function flatten($arguments, array &$list)
    {
        foreach ($arguments as $key => $item) {
            if (!is_numeric($key)) {
                $list[] = $key;
            }

            if (is_scalar($item)) {
                $list[] = strval($item);
            } elseif (null === $item) {
                $list[] = '<null>';
            } else {
                $this->flatten($item, $list);
            }
        }
    }

EOF;

$rc = new \ReflectionClass('\Redis');
$reflectedMethods = $rc->getMethods(\ReflectionMethod::IS_PUBLIC);

/* @var $reflectedMethod \ReflectionMethod */
foreach ($reflectedMethods as $reflectedMethod) {
    if ($reflectedMethod->isFinal() || $reflectedMethod->isConstructor() || PHP_VERSION_ID < 70000 && in_array($reflectedMethod->getName(), ['echo', 'eval'], true)) {
        continue;
    }

    $method = "\n    /**\n";
    $method .= "     * {@inheritdoc}\n";
    $method .= "     */\n";

    $method .= '    ';

    if ($reflectedMethod->isAbstract()) {
        $method .= 'abstract ';
    }
    $method .= 'public ';
    if ($reflectedMethod->isStatic()) {
        $method .= 'static ';
    }
    $method .= 'function ';
    $method .= $reflectedMethod->getName();
    $method .= '(';
    $args = $reflectedMethod->getParameters();
    /* @var $rp \ReflectionParameter */
    foreach ($args as $rp) {
        if (method_exists($rp, 'getType') && $rp->getType()) {
            $method .= $rp->getType();
            $method .= ' ';
        }
        if (method_exists($rp, 'isVariadic') && $rp->isVariadic()) {
            $method .= '...';
        }
        if ($rp->isPassedByReference()) {
            $method .= '&';
        }
        $method .= '$';
        $method .= $rp->getName();
        if ($rp->isDefaultValueAvailable() || $rp->isOptional()) {
            $method .= ' = ';
            $method .= $rp->isDefaultValueAvailable() ? $rp->getDefaultValue() : 'null';
        }
        if ($rp->getPosition() < count($reflectedMethod->getParameters()) - 1) {
            $method .= ', ';
        }
    }

    $method .= ')';

    if (method_exists($reflectedMethod, 'hasReturnType') && $reflectedMethod->hasReturnType()) {
        $method .= ': ';
        $method .= $reflectedMethod->getReturnType();
    }

    $method .= "\n";
    $method .= "    {\n";
    $method .= '        return $this->call(\''.$reflectedMethod->getName().'\'';
    if (count($args)) {
        $method .= ', array(';
        foreach ($args as $rp) {
            if ($rp->isPassedByReference()) {
                $method .= '&';
            }
            $method .= '$';
            $method .= $rp->getName();
            if ($rp->getPosition() < count($reflectedMethod->getParameters()) - 1) {
                $method .= ', ';
            }
        }
        $method .= ')';
    }
    $method .= ");\n";
    $method .= "    }\n";

    $class .= $method;
}
$class .= "}\n";

$classname = 'Client'.str_replace('.', '_', phpversion('redis'));
$class = str_replace('{{classname}}', $classname, $class);
$file = __DIR__.'/../Client/Phpredis/'.$classname.'.php';

file_put_contents($file, $class);

echo $class;
