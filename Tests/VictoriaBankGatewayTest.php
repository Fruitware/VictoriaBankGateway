<?php

namespace Fruitware\VictoriaBankGateway\Tests;

use Fruitware\VictoriaBankGateway\VictoriaBankGateway;

class VictoriaBankGatewayTest extends \PHPUnit_Framework_TestCase
{
    public function testInit() {
        $victoriaBankGatewayTest =  new VictoriaBankGateway();
        $victoriaBankGatewayTest
            ->configureFromEnv(__DIR__.'/certificates')
            ->setDebug(false)
            ->setDefaultLanguage('en')
        ;
        $this->assertEquals('1', '1');
    }
}