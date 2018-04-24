<?php

namespace Snc\RedisBundle\Tests\Factory;

use PHPUnit\Framework\TestCase;
use Snc\RedisBundle\Factory\EnvParametersFactory;

class EnvParametersFactoryTest extends TestCase
{
    public function createDp()
    {
        return array(
            array(
                'redis://z:df577d779b4f724c8c29b5eff5bcc534b732722b9df308a661f1b79014175063d5@ec2-34-321-123-45.us-east-1.compute.amazonaws.com:3210',
                'Predis\Connection\Parameters',
                array(
                    'test' => 123,
                    'some' => 'string',
                    'arbitrary' => true,
                    'values' => array(1, 2, 3)
                )
            ),
            array(
                'redis://password@host:4711',
                'Predis\Connection\Parameters',
                array()
            )
        );
    }

    /**
     * @param $dsn
     * @param $class
     * @param $options
     *
     * @dataProvider createDp
     */
    public function testCreate($dsn, $class, $options)
    {
        $parameters = EnvParametersFactory::create($options, $class, $dsn);

        $this->assertInstanceOf($class, $parameters);

        foreach ($options as $optionName => $optionValue) {
            $this->assertEquals($optionValue, $parameters->{$optionName});
        }
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testCreateException()
    {
        EnvParametersFactory::create(array(), '\stdClass', 'redis://localhost');
    }
}
