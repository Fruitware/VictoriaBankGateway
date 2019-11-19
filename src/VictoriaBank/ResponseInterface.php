<?php

namespace Fruitware\VictoriaBankGateway\VictoriaBank;

/**
 * Interface ResponseInterface
 *
 * @package Fruitware\VictoriaBankGateway\VictoriaBank
 */
interface ResponseInterface
{
    const TRX_TYPE = 0;

    const TERMINAL = 'TERMINAL';   #Size: 8, Echo from the request
    const TRTYPE   = 'TRTYPE';     #Size: 2, Echo from the request
    const ORDER    = 'ORDER';      #Size: 6-32, Echo from the request
    const AMOUNT   = 'AMOUNT';     #Size: 12, Echo from the request
    const CURRENCY = 'CURRENCY';   #Size: 3, Echo from the request
    const ACTION   = 'ACTION';     #Size: 1, E-Gateway action code: 0 – Transaction successfully completed;1 – Duplicate transaction detected;2 – Transaction declined;3 – Transaction processing fault.
    const RC        = 'RC';         #Size: 02, Transaction response code (ISO-8583 Field 39)
    const TEXT      = 'TEXT';
    const APPROVAL  = 'APPROVAL';   #Size: 06, Client bank’s approval code (ISO-8583 Field 38). Can be empty if not provided by card management system.
    const RRN       = 'RRN';        #Size: 12, Merchant bank’s retrieval reference number (ISO-8583 Field 37).
    const INT_REF   = 'INT_REF';    #Size: 1-32, E-Commerce gateway internal reference number
    const TIMESTAMP = 'TIMESTAMP';  #Size: 14, E-Commerce gateway timestamp in GMT: YYYYMMDDHHMMSS
    const NONCE     = 'NONCE';      #Size: 1-64, E-Commerce gateway nonce value. Will be filled with 8-32 unpredictable random bytes in hexadecimal format. Will be present if MAC is used.
    const P_SIGN    = 'P_SIGN';     #Size: 1-256, E-Commerce gateway MAC (Message Authentication Code) in hexadecimal form. Will be present if MAC is used.
    const BIN       = 'BIN';
    const CARD      = 'CARD';
    const AUTH      = 'AUTH';
    const ECI       = 'ECI';        #Size: 0-02, Electronic Commerce Indicator (ECI): ECI=empty – Technical fault;ECI=05 - Secure electronic commerce transaction (fully 3-D Secure authenticated);ECI=06 - Non-authenticated security transaction at a 3-D Secure-capable merchant, and merchant attempted to authenticate the cardholder using 3-D Secure but was unable to complete the authentication because the issuer or cardholder does not participate in the 3-D Secure program;ECI=07 - Non-authenticated Security Transaction

    const STATUS_SUCCESS    = 0;        #Transaction successfully completed
    const STATUS_DUPLICATED = 1;        #Duplicate transaction detected
    const STATUS_DECLINED   = 2;        #Transaction declined
    const STATUS_FAULT      = 3;        #Transaction processing fault

    /**
     * Construct
     *
     * @param array $responseData
     *
     * @throws Exception
     */
    public function __construct(array $responseData);

    /**
     * Validates response
     *
     * @return bool
     */
    public function isValid();

    /**
     * @return array
     */
    public function getErrors();

    /**
     * @return mixed
     */
    public function getLastError();

    /**
     * Magic method to get response fields
     *
     * @param $fieldName
     *
     * @return null
     */
    public function __get($fieldName);
}