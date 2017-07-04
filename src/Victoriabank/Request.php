<?php

namespace Fruitware\VictoriaBankGateway\VictoriaBank;

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
     * @var bool
     */
    protected $_debugMode = false;

    /**
     * @var string
     */
    protected $_gatewayUrl = 'https://egateway.victoriabank.md/cgi-bin/cgi_link';

    /**
     * @var array
     */
    protected $_requestFields = [];

    /**
     * Construct
     *
     * @param array $requestParams
     * @param bool  $debugMode
     *
     * @throws Exception
     */
    public function __construct(array $requestParams, $debugMode = false)
    {
        #Push the request field values
        foreach ($requestParams as $name => $value) {
            if (!array_key_exists($name, $this->_requestFields)) {
                continue;
            }
            $this->_requestFields[$name] = $value;
        }
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
     * @param float $amount
     *
     * @return string
     * @throws Exception
     */
    protected function _createSignature($order, $nonce, $timestamp, $trType, $amount)
    {
        if (empty($order) || empty($nonce) || empty($timestamp) || is_null($trType) || empty($amount)) {
            throw new Exception('Failed to generate transaction signature: Invalid request params');
        }
        if (!file_exists(self::$privateKeyPath) || !$rsaKey = file_get_contents(self::$privateKeyPath)) {
            throw new Exception('Failed to generate transaction signature: Private key not accessible');
        }
        $rsaKeyResource = openssl_get_privatekey($rsaKey);
        if (!$rsaKeyResource) {
            throw new Exception('Failed to generate transaction signature: Failed to get private key');
        }
        $rsaKeyDetails = openssl_pkey_get_details($rsaKeyResource);
        $rsaKeyLength  = $rsaKeyDetails['bits'] / 8;
        // Data before md5
        $mac     = strlen($order).$order.strlen($nonce).$nonce.strlen($timestamp).$timestamp.strlen($trType).$trType.strlen($amount).$amount;
        $macHash = md5($mac);
        // Concatenated mac with encryption data
        $signature     = self::$signatureFirst;
        $paddingLength = $rsaKeyLength - strlen($macHash) / 2 - strlen(self::$signaturePrefix) / 2 - strlen(self::$signatureFirst) / 2;
        for ($i = 0; $i < $paddingLength; $i++) {
            $signature .= self::$signaturePadding;
        }
        $signature .= self::$signaturePrefix.$macHash;
        $bin       = pack("H*", $signature);
        if (!openssl_private_encrypt($bin, $encryptedBin, $rsaKeyResource, OPENSSL_NO_PADDING)) {
            $errorMsg = '';
            while ($msg = openssl_error_string()) {
                echo $errorMsg."<br />\n";
            }
            #throw new Exception('Failed to generate transaction signature: Failed to encrypt the bin - ' . $errorMsg);
            throw new Exception('Failed to generate transaction signature: Failed to encrypt the bin');
        }
        $pSign = bin2hex($encryptedBin);

        return $pSign;
    }
}