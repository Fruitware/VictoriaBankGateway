<?php

namespace Fruitware\VictoriaBankGateway\VictoriaBank;

/**
 * Interface RequestInterface
 *
 * @package Fruitware\VictoriaBankGateway\VictoriaBank
 */
interface RequestInterface
{
    /**
     * RequestInterface constructor.
     *
     * @param array $requestParams
     * @param bool  $debugMode
     */
    public function __construct(array $requestParams, $debugMode = false);

    /**
     * @param bool $debugMode
     *
     * @return $this
     */
    public function setDebugMode($debugMode);

    /**
     * Performs the actual request
     * @return mixed
     */
    public function request();
}