<?php

namespace Fruitware\VictoriaBankGateway\VictoriaBank;

use Fruitware\VictoriaBankGateway\VictoriaBankGateway;

abstract class Request implements RequestInterface
{
    /**
     * Provided by VictoriaBank
     * @var null
     */
    static public $signatureFirst;

    /**
     * Provided by VictoriaBank
     * @var null
     */
    static public $signaturePrefix;

    /**
     * Provided by VictoriaBank
     * @var string
     */
    static public $signaturePadding;

    /**
     * The path to the public key - not used
     * @var string
     */
    static public $publicKeyPath;

    /**
     * The path to the private key
     * @var string
     */
    static public $privateKeyPath;

    /**
     * Private key passphrase
     * @var string
     */
    static public $privateKeyPass;

    /**
     * @var bool
     */
    protected $_debugMode = false;

    /**
     * @var string
     */
    protected $_gatewayUrl;

    /**
     * @var array
     */
    protected $_requestFields = [];

    /**
     * Construct
     *
     * @param array  $requestParams
     * @param string $gatewayUrl
     * @param bool   $debugMode
     *
     * @throws Exception
     */
    public function __construct(array $requestParams, $gatewayUrl, $debugMode = false)
    {
        #Push the request field values
        foreach ($requestParams as $name => $value) {
            if (!array_key_exists($name, $this->_requestFields)) {
                continue;
            }
            $this->_requestFields[$name] = $value;
        }

        #Set gateway URL
        $this->_gatewayUrl = $gatewayUrl;
        #Set debug mode
        $this->_debugMode = $debugMode;

        #Make sure to set these static params prior to calling the request
        if (is_null(self::$signatureFirst)) {
            throw new Exception('Could not instantiate the bank request - missing parameter signatureFirst');
        }
        if (is_null(self::$signaturePrefix)) {
            throw new Exception('Could not instantiate the bank request - missing parameter signaturePrefix');
        }
        if (is_null(self::$signaturePadding)) {
            throw new Exception('Could not instantiate the bank request - missing parameter signaturePadding');
        }
        if (is_null(self::$privateKeyPath)) {
            throw new Exception('Could not instantiate the bank request - missing parameter privateKeyPath');
        }
        $this->init();
    }

    /**
     * Initialization
     */
    protected function init()
    {
        $this->validateRequestParams();

        return $this;
    }

    /**
     * @return mixed
     */
    abstract public function validateRequestParams();

    /**
     * @param boolean $debugMode
     *
     * @return $this
     */
    public function setDebugMode($debugMode)
    {
        $this->_debugMode = (boolean)$debugMode;

        return $this;
    }

    /**
     * @param string $gatewayUrl
     *
     * @return $this
     */
    public function setGatewayUrl($gatewayUrl)
    {
        $this->_gatewayUrl = $gatewayUrl;

        return $this;
    }

    /**
     * Performs the actual request
     * @return mixed
     */
    abstract public function request();

    /**
     * Generates the P_SIGN
     *
     * @param string $order
     * @param string $nonce
     * @param string $timestamp
     * @param string $trType
     * @param float  $amount
     *
     * @return string
     * @throws Exception
     */
    protected function _createSignature($order, $nonce, $timestamp, $trType, $amount)
    {
        $mac = '';
        if (empty($order) || empty($nonce) || empty($timestamp) || is_null($trType) || empty($amount)) {
            throw new Exception('Failed to generate transaction signature: Invalid request params');
        }
        if (!file_exists(self::$privateKeyPath) || !$rsaKey = file_get_contents(self::$privateKeyPath)) {
            throw new Exception('Failed to generate transaction signature: Private key not accessible');
        }
        $data = [
            'ORDER' => VictoriaBankGateway::normalizeOrderId($order),
            'NONCE' => $nonce,
            'TIMESTAMP' => $timestamp,
            'TRTYPE' => $trType,
            'AMOUNT' => VictoriaBankGateway::normalizeAmount($amount),
        ];
        if (!$rsaKeyResource = openssl_get_privatekey($rsaKey, self::$privateKeyPass)) {
            die ('Failed get private key');
        }
        $rsaKeyDetails = openssl_pkey_get_details($rsaKeyResource);
        $rsaKeyLength  = $rsaKeyDetails['bits'] / 8;
        foreach ($data as $Id => $filed) {
            $mac .= strlen($filed).$filed;
        }
        $first   = static::$signatureFirst;
        $prefix  = static::$signaturePadding.static::$signaturePrefix;
        $md5Hash = md5($mac);
        $data    = $first;
        $paddingLength = $rsaKeyLength - strlen($md5Hash) / 2 - strlen($prefix) / 2 - strlen($first) / 2;
        for ($i = 0; $i < $paddingLength; $i++) {
            $data .= "FF";
        }
        $data .= $prefix.$md5Hash;
        $bin  = pack("H*", $data);
        if (!openssl_private_encrypt($bin, $encryptedBin, $rsaKey, OPENSSL_NO_PADDING)) {
            while ($msg = openssl_error_string()) {
                echo $msg."<br />\n";
            }
            die ('Failed encrypt');
        }
        $pSign = bin2hex($encryptedBin);

        return strtoupper($pSign);
    }
}