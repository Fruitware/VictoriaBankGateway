<?php

namespace Fruitware\VictoriaBankGateway\Tests;

use Fruitware\VictoriaBankGateway\VictoriaBankGateway;
use PHPUnit_Framework_TestCase;

/**
 * Class VictoriaBankGatewayTest
 *
 * @package Fruitware\VictoriaBankGateway\Tests
 */
class VictoriaBankGatewayTest extends PHPUnit_Framework_TestCase
{
    public function testInit() {
        $victoriaBankGatewayTest =  new VictoriaBankGateway();
        $victoriaBankGatewayTest
            ->configureFromEnv(__DIR__.'/certificates')
            ->setDebug(false)
            ->setDefaultLanguage('en')
        ;
        static::assertEquals('1', '1');
    }
}