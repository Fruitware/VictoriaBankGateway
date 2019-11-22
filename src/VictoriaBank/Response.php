<?php

namespace Fruitware\VictoriaBankGateway\VictoriaBank;

use Fruitware\VictoriaBankGateway\VictoriaBankGateway;

/**
 * Class Response
 */
abstract class Response implements ResponseInterface
{
    /**
     * Public key is provided by VictoriaBank
     * @var string
     */
    static public $bankPublicKeyPath;

    /**
     * Provided by VictoriaBank
     * @var string
     */
    static public $signaturePrefix;

    /**
     * @var array
     */
    protected $_responseFields = [
        self::TERMINAL => null,
        self::TRTYPE => null,
        self::ORDER => null,
        self::AMOUNT => null,
        self::CURRENCY => null,
        self::ACTION => null,
        self::RC => null,
        self::TEXT => null,
        self::APPROVAL => null,
        self::RRN => null,
        self::INT_REF => null,
        self::TIMESTAMP => null,
        self::NONCE => null,
        self::P_SIGN => null,
        self::BIN => null,
        self::CARD => null,
        self::AUTH => null,
        self::ECI => null,
    ];

    /**
     * @var array
     */
    protected $_errors = [];

    /**
     * Construct
     *
     * @param array $responseData
     *
     * @throws Exception
     */
    public function __construct(array $responseData)
    {
        #Make sure to set these static params prior to calling the response
        if (is_null(self::$bankPublicKeyPath)) {
            throw new Exception('Could not instantiate the bank response - missing parameter bankPublicKeyPath');
        }
        if (is_null(self::$signaturePrefix)) {
            throw new Exception('Could not instantiate the bank response - missing parameter signaturePrefix');
        }
        if (empty($responseData)) {
            throw new Exception('Bank response error: Empty data received');
        }
        #Set the response fields
        foreach ($this->_responseFields as $k => &$v) {
            if (isset($responseData[$k])) {
                $v = $responseData[$k];
            }
        }

        $this->_responseFields[self::ORDER] = VictoriaBankGateway::normalizeOrderId($this->_responseFields[self::ORDER]);
        $this->_responseFields[self::AMOUNT] = VictoriaBankGateway::normalizeAmount($this->_responseFields[self::AMOUNT]);

        return $this;
    }

    /**
     * Validates response
     *
     * @return bool
     */
    public function isValid()
    {
        try {
            $isValid = $this->_validateResponse();
        } catch (Exception $e) {
            $isValid         = false;
            $this->_errors[] = $e->getMessage();
        }

        return $isValid;
    }

    /**
     * Validates the response
     *
     * @return bool
     * @throws Exception
     */
    protected function _validateResponse()
    {
        if (!isset($this->_responseFields[self::ACTION])) {
            throw new Exception('Bank response: Invalid data received');
        }
        switch ((int)$this->_responseFields[self::ACTION]) {
            case self::STATUS_SUCCESS:
                return $this->_validateSignature();
            case self::STATUS_DUPLICATED:
                throw new Exception('Bank response: Duplicate transaction');
            case self::STATUS_DECLINED:
                throw new Exception('Bank response: Transaction declined');
            case self::STATUS_FAULT:
                throw new Exception('Bank response: Processing fault');
            default:
                throw new Exception('Undefined bank response status');
        }
    }

    /**
     * @return bool
     * @throws Exception
     */
    protected function _validateSignature()
    {
        $mac = '';
        foreach ([self::ACTION, self::RC, self::RRN, self::ORDER, self::AMOUNT] as $field) {
            $value = $this->_responseFields[$field];
            if ($value != '-') {
                $mac .= strlen($value).$value;
            } else {
                $mac .= $value;
            }
        }
        $macHash      = strtoupper(md5($mac));
        $pSign        = $this->_responseFields[self::P_SIGN];
        $encryptedBin = hex2bin($pSign);
        if (!file_exists(self::$bankPublicKeyPath) || !$rsaKey = file_get_contents(self::$bankPublicKeyPath)) {
            throw new Exception('Failed to generate response signature: Bank key not accessible');
        }
        if (!$rsaKeyResource = openssl_get_publickey($rsaKey)) {
            throw new Exception('Failed to generate response signature: Failed to init bank key');
        }
        if (!openssl_public_decrypt($encryptedBin, $decryptedBin, $rsaKey)) {
            $errorMsg = '';
            while ($msg = openssl_error_string()) {
                $errorMsg .= $msg."<br />\n";
            }
            throw new Exception('Failed decrypt response signature: '.$errorMsg);
        }
        $decrypted     = strtoupper(bin2hex($decryptedBin));
        $decryptedHash = str_replace(self::$signaturePrefix, '', $decrypted);

        return $decryptedHash == $macHash;
    }

    /**
     * @return array
     */
    public function getErrors()
    {
        return $this->_errors;
    }

    /**
     * @return mixed
     */
    public function getLastError()
    {
        return end($this->_errors);
    }

    /**
     * Magic method to get response fields
     *
     * @param $fieldName
     *
     * @return null
     */
    public function __get($fieldName)
    {
        if (!isset($this->_responseFields[$fieldName])) {
            return null;
        }

        return $this->_responseFields[$fieldName];
    }
}