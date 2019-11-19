<?php

namespace Fruitware\VictoriaBankGateway\VictoriaBank\Completion;

use Fruitware\VictoriaBankGateway\VictoriaBank\Exception;
use Fruitware\VictoriaBankGateway\VictoriaBank\Request;
use Fruitware\VictoriaBankGateway\VictoriaBankGateway;

/**
 * Class CompletionRequest
 *
 * @package Fruitware\VictoriaBankGateway\VictoriaBank\Completion
 */
class CompletionRequest extends Request
{

    #Sales completion message fields provided by the merchant system
    const ORDER     = 'ORDER';          #Size: 6-32, Merchant order ID from request
    const AMOUNT    = 'AMOUNT';         #Size: 12, Transaction amount. Float format with decimal point separator.
    const CURRENCY  = 'CURRENCY';       #Size: 3, Currency name. Must be the same as in authorization response.
    const RRN       = 'RRN';            #Size: 12, Retrieval reference number from authorization response.
    const INT_REF   = 'INT_REF';        #Size: 1-32, Internal reference number from authorization response.
    const TRTYPE    = 'TRTYPE';         #Size: 2, Must be equal to "21" (Sales completion).
    const TERMINAL  = 'TERMINAL';       #Size: 8, Merchant terminal ID assigned by bank. Must be equal to "TERMINAL" field from authorization request.
    const TIMESTAMP = 'TIMESTAMP';      #Size: 14, Merchant transaction timestamp in GMT: YYYYMMDDHHMMSS. Timestamp difference between Internet shop and e-Gateway must not exceed 1 hour otherwise e-Gateway will reject this transaction.
    const NONCE     = 'NONCE';          #Size: 1-64, Merchant nonce. Must be filled with 8-32 unpredictable random bytes in hexadecimal format. Must be present if MAC is used.
    const P_SIGN    = 'P_SIGN';         #Size: 1-256, Merchant MAC in hexadecimal form.

    #Request fields
    protected $_requestFields = [
        self::ORDER => null,
        self::AMOUNT => null,
        self::CURRENCY => null,
        self::RRN => null,
        self::INT_REF => null,
        self::TRTYPE => null,
        self::TERMINAL => null,
        self::TIMESTAMP => null,
        self::NONCE => null,
        self::P_SIGN => null,
    ];

    /**
     * @return \Fruitware\VictoriaBankGateway\VictoriaBank\Request|void
     * @throws \Fruitware\VictoriaBankGateway\VictoriaBank\Exception
     */
    protected function init()
    {
        parent::init();
        #Set TRX type
        $this->_requestFields[self::TRTYPE] = VictoriaBankGateway::TRX_TYPE_COMPLETION;
        #Set TRX signature
        $order                              = $this->_requestFields[self::ORDER];
        $nonce                              = $this->_requestFields[self::NONCE];
        $timestamp                          = $this->_requestFields[self::TIMESTAMP];
        $trType                             = $this->_requestFields[self::TRTYPE];
        $amount                             = $this->_requestFields[self::AMOUNT];
        $this->_requestFields[self::P_SIGN] = $this->_createSignature($order, $nonce, $timestamp, $trType, $amount);
    }

    /**
     * @return $this|mixed
     * @throws Exception
     */
    public function validateRequestParams()
    {
        if (!isset($this->_requestFields[self::AMOUNT]) || strlen($this->_requestFields[self::AMOUNT]) < 1 || strlen(
                                                                                                                  $this->_requestFields[self::AMOUNT]
                                                                                                              ) > 12) {
            throw new Exception('Authorization request failed: invalid '.self::AMOUNT);
        }
        if (!isset($this->_requestFields[self::CURRENCY]) || strlen($this->_requestFields[self::CURRENCY]) != 3) {
            throw new Exception('Authorization request failed: invalid '.self::CURRENCY);
        }
        if (!isset($this->_requestFields[self::ORDER]) || strlen($this->_requestFields[self::ORDER]) < 6 || strlen(
                                                                                                                $this->_requestFields[self::ORDER]
                                                                                                            ) > 32) {
            throw new Exception('Authorization request failed: invalid '.self::ORDER);
        }
        if (!isset($this->_requestFields[self::TERMINAL]) || strlen($this->_requestFields[self::TERMINAL]) != 8) {
            throw new Exception('Authorization request failed: invalid '.self::TERMINAL);
        }
        if (!isset($this->_requestFields[self::TIMESTAMP]) || strlen($this->_requestFields[self::TIMESTAMP]) != 14) {
            throw new Exception('Authorization request failed: invalid '.self::TIMESTAMP);
        }
        if (!isset($this->_requestFields[self::NONCE]) || strlen($this->_requestFields[self::NONCE]) < 20 || strlen(
                                                                                                                 $this->_requestFields[self::NONCE]
                                                                                                             ) > 32) {
            throw new Exception('Authorization request failed: invalid '.self::NONCE);
        }
        if (!isset($this->_requestFields[self::RRN]) || strlen($this->_requestFields[self::RRN]) != 12) {
            throw new Exception('Authorization request failed: invalid '.self::RRN);
        }
        if (!isset($this->_requestFields[self::INT_REF]) || strlen($this->_requestFields[self::INT_REF]) < 1 || strlen(
                                                                                                                    $this->_requestFields[self::INT_REF]
                                                                                                                ) > 32) {
            throw new Exception('Authorization request failed: invalid '.self::INT_REF);
        }

        return $this;
    }

    /**
     * Prepares the form to be submitted to the payment gateway and performs the redirect
     * @return bool|string
     */
    public function request()
    {
        $options = [
            'http' => [
                'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                'method' => 'POST',
                'content' => http_build_query($this->_requestFields),
            ],
        ];
        $context = stream_context_create($options);
        $result  = file_get_contents($this->_gatewayUrl, false, $context);

        return $result;
    }
}