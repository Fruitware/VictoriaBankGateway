<?php

namespace Fruitware\VictoriaBankGateway;

use Fruitware\VictoriaBankGateway\VictoriaBank;
use Fruitware\VictoriaBankGateway\VictoriaBank\ResponseInterface;

class VictoriaBankGateway
{
    const TRX_TYPE_AUTHORIZATION = 0;
    const TRX_TYPE_COMPLETION    = 21;
    const TRX_TYPE_REVERSAL      = 24;

    /**
     * @var bool
     */
    private $debug = false;

    /**
     * @var string
     */
    private $merchant;

    /**
     * @var string
     */
    private $terminal;

    /**
     * @see https://en.wikipedia.org/wiki/List_of_ISO_639-1_codes
     *
     * @var array
     */
    private $supportedLanguages = ['en', 'ro', 'ru'];

    /**
     * @see http://php.net/manual/en/timezones.php
     *
     * @var string
     */
    private $timezoneName;

    /**
     * @see https://en.wikipedia.org/wiki/ISO_4217
     *
     * @var string
     */
    private $defaultCurrency = 'MDL';

    /**
     * @see https://en.wikipedia.org/wiki/List_of_ISO_639-1_codes
     *
     * @var string
     */
    private $defaultLanguage = 'en';

    /**
     * @var string
     */
    private $countryCode = 'md';

    /**
     * @var string
     */
    private $merchantName;

    /**
     * @var string
     */
    private $merchantUrl;

    /**
     * @var string
     */
    private $merchantAddress;

    /**
     * VictoriaBankGateway constructor.
     */
    public function __construct()
    {
        $this->timezoneName = date_default_timezone_get();
    }

    /**
     * @param string $certDir
     *
     * @return $this
     */
    public function configureFromEnv($certDir)
    {
        $certDir = rtrim($certDir);
        // Set basic info
        $this
            ->setMerchantId(getenv('VICTORIA_BANK_MERCHANT_ID'))
            ->setMerchantTerminal(getenv('VICTORIA_BANK_MERCHANT_TERMINAL'))
            ->setMerchantUrl(getenv('VICTORIA_BANK_MERCHANT_URL'))
            ->setMerchantName(getenv('VICTORIA_BANK_MERCHANT_NAME'))
            ->setMerchantAddress(getenv('VICTORIA_BANK_MERCHANT_ADDRESS'))
            ->setTimezone(getenv('VICTORIA_BANK_MERCHANT_TIMEZONE_NAME'))
            ->setCountryCode(getenv('VICTORIA_BANK_MERCHANT_COUNTRY_CODE'))
            ->setDefaultCurrency(getenv('VICTORIA_BANK_MERCHANT_DEFAULT_CURRENCY'))
            ->setDefaultLanguage(getenv('VICTORIA_BANK_MERCHANT_DEFAULT_LANGUAGE'))
        ;
        //Set security options - provided by the bank
        $signatureFirst    = getenv('VICTORIA_BANK_SECURITY_SIGNATURE_FIRST');
        $signaturePrefix   = getenv('VICTORIA_BANK_SECURITY_SIGNATURE_PREFIX');
        $signaturePadding  = getenv('VICTORIA_BANK_SECURITY_SIGNATURE_PADDING');
        $publicKeyPath     = $certDir.'/'.getenv('VICTORIA_BANK_MERCHANT_PUBLIC_KEY');
        $privateKeyPath    = $certDir.'/'.getenv('VICTORIA_BANK_MERCHANT_PRIVATE_KEY');
        $bankPublicKeyPath = $certDir.'/'.getenv('VICTORIA_BANK_MERCHANT_BANK_PUBLIC_KEY');
        $this
            ->setSecurityOptions($signatureFirst, $signaturePrefix, $signaturePadding, $publicKeyPath, $privateKeyPath, $bankPublicKeyPath);

        return $this;
    }

    /**
     * @return \DateTimeZone
     */
    protected function getMerchantTimeZone()
    {
        return new \DateTimeZone($this->timezoneName);
    }

    /**
     * Merchant transaction timestamp in GMT: YYYYMMDDHHMMSS.
     * Timestamp difference between merchant server and e-Gateway
     * server must not exceed 1 hour, otherwise e-Gateway will reject this transaction
     *
     * @return string
     */
    protected function getTransactionTimestamp()
    {
        $date = new \DateTime('now', $this->getMerchantTimeZone());
        $date->setTimezone(new \DateTimeZone('GMT'));

        return $date->format('YmdHis');
    }

    /**
     * Merchant UTC/GMT time zone offset (e.g. –3).
     * Must be provided if merchant system is located
     * in a time zone other than the gateway server's time zone.
     *
     * @return string
     */
    protected function getMerchantGmtTimezoneOffset()
    {
        $dateTimeZone   = $this->getMerchantTimeZone();
        $timezoneOffset = (float)$dateTimeZone->getOffset(new \DateTime()) / 3600;
        if ($timezoneOffset > 0) {
            $timezoneOffset = '+'.$timezoneOffset;
        }

        return (string)$timezoneOffset;
    }

    /**
     * Debug mode setter
     *
     * @param boolean $debug
     *
     * @return $this
     */
    public function setDebug($debug)
    {
        $this->debug = (boolean)$debug;

        return $this;
    }

    /**
     * Set Timezone name
     * Used to calculate the timezone offset sent to VictoriaBank
     *
     * @param $tzName
     *
     * @return $this
     */
    public function setTimezone($tzName)
    {
        $this->timezoneName = $tzName;

        return $this;
    }

    /**
     * Add custom supported language
     * @see https://en.wikipedia.org/wiki/List_of_ISO_639-1_codes
     *
     * If need forms in another languages please contact gateway administrator
     *
     * @param string $lang
     *
     * @return $this
     */
    public function addSupportedLanguage($lang)
    {
        $lang                       = strtolower(trim($lang));
        $this->supportedLanguages[] = $lang;

        return $this;
    }

    /**
     * Transaction forms language.
     * By default are available forms in en, ro, ru.
     * @see https://en.wikipedia.org/wiki/List_of_ISO_639-1_codes
     *
     * If need forms in another languages please contact gateway administrator
     * @see addSupportedLanguage()
     *
     * @param string $lang
     *
     * @return $this
     * @throws VictoriaBank\Exception
     */
    public function setDefaultLanguage($lang)
    {
        $lang = strtolower(trim($lang));
        if (!in_array($lang, $this->supportedLanguages, true)) {
            throw new VictoriaBank\Exception("The language '{$lang}' is not accepted by VictoriaBank");
        }
        $this->defaultLanguage = $lang;

        return $this;
    }

    /**
     * Merchant shop 2-character country code. Must be provided if
     * merchant system is located in a country other than the gateway
     * server's country.
     * @see https://en.wikipedia.org/wiki/ISO_3166-1_alpha-2
     *
     * @param $countryCode - two letter country code
     *
     * @return $this
     */
    public function setCountryCode($countryCode)
    {
        $this->countryCode = strtolower(trim($countryCode));

        return $this;
    }

    /**
     * Set default currency for all operations
     *
     * @param int $currency 3-character currency code
     *
     * @return $this
     */
    public function setDefaultCurrency($currency)
    {
        $this->defaultCurrency = $currency;

        return $this;
    }

    /**
     * Set Merchant Terminal ID assigned by bank
     *
     * @param int $terminal
     *
     * @return $this
     */
    public function setMerchantTerminal($terminal)
    {
        $this->terminal = $terminal;

        return $this;
    }

    /**
     * Set Merchant ID assigned by bank
     *
     * @param int $id
     *
     * @return $this
     */
    public function setMerchantId($id)
    {
        $this->merchant = $id;

        return $this;
    }

    /**
     * Merchant name setter
     *
     * @param $name
     *
     * @return $this
     */
    public function setMerchantName($name)
    {
        $this->merchantName = $name;

        return $this;
    }

    /**
     * Merchant address setter
     *
     * @param $address
     *
     * @return $this
     */
    public function setMerchantAddress($address)
    {
        $this->merchantAddress = $address;

        return $this;
    }

    /**
     * Set Merchant primary web site URL
     *
     * @param $url
     *
     * @return $this
     */
    public function setMerchantUrl($url)
    {
        $this->merchantUrl = $url;

        return $this;
    }

    public function setSecurityOptions($signatureFirst, $signaturePrefix, $signaturePadding, $publicKeyPath, $privateKeyPath, $bankPublicKeyPath, $privateKeyPass='')
    {
        #Request security options
        VictoriaBank\Request::$signatureFirst   = $signatureFirst;
        VictoriaBank\Request::$signaturePrefix  = $signaturePrefix;
        VictoriaBank\Request::$signaturePadding = $signaturePadding;
        VictoriaBank\Request::$publicKeyPath    = $publicKeyPath;
        VictoriaBank\Request::$privateKeyPath   = $privateKeyPath;
        VictoriaBank\Request::$privateKeyPass   = $privateKeyPass;
        #Response security options
        VictoriaBank\Response::$signaturePrefix   = $signaturePrefix;
        VictoriaBank\Response::$bankPublicKeyPath = $bankPublicKeyPath;

        return $this;
    }

    /**
     * Perform an authorization request
     *
     * @param string $orderId     Merchant order ID
     * @param float  $amount      Order total amount in float format with decimal point separator
     * @param string $backRefUrl  Merchant URL for redirecting the client after receiving transaction result
     * @param string $currency    Order currency: 3-character currency code
     * @param string $description Order description
     * @param string $clientEmail Client e-mail address
     * @param string $language    Transaction forms language
     *
     * @throws VictoriaBank\Exception
     */
    public function requestAuthorization($orderId, $amount, $backRefUrl, $currency = null, $description = null, $clientEmail = null, $language = null)
    {
        try {
            $request = new VictoriaBank\Authorization\AuthorizationRequest(
                [
                    VictoriaBank\Authorization\AuthorizationRequest::TERMINAL => $this->terminal,
                    VictoriaBank\Authorization\AuthorizationRequest::ORDER => $this->normalizeOrderId($orderId),
                    VictoriaBank\Authorization\AuthorizationRequest::AMOUNT => $this->normalizeAmount($amount),
                    VictoriaBank\Authorization\AuthorizationRequest::CURRENCY => $currency ? $currency : $this->defaultCurrency,
                    VictoriaBank\Authorization\AuthorizationRequest::TIMESTAMP => $this->getTransactionTimestamp(),
                    VictoriaBank\Authorization\AuthorizationRequest::NONCE => $this->generateNonce(),
                    VictoriaBank\Authorization\AuthorizationRequest::DESC => $description ? $description : "Order {$orderId} payment",
                    VictoriaBank\Authorization\AuthorizationRequest::EMAIL => (string)$clientEmail,
                    VictoriaBank\Authorization\AuthorizationRequest::COUNTRY => $this->countryCode,
                    VictoriaBank\Authorization\AuthorizationRequest::BACKREF => $backRefUrl,
                    VictoriaBank\Authorization\AuthorizationRequest::MERCH_GMT => $this->getMerchantGmtTimezoneOffset(),
                    VictoriaBank\Authorization\AuthorizationRequest::LANG => $language ? $language : $this->defaultLanguage,
                    VictoriaBank\Authorization\AuthorizationRequest::MERCHANT => $this->merchant,
                    VictoriaBank\Authorization\AuthorizationRequest::MERCH_NAME => $this->merchantName,
                    VictoriaBank\Authorization\AuthorizationRequest::MERCH_URL => $this->merchantUrl,
                    VictoriaBank\Authorization\AuthorizationRequest::MERCH_ADDRESS => $this->merchantAddress,
                ], $this->debug
            );
            $request->request();
        } catch (VictoriaBank\Exception $e) {
            if ($this->debug) {
                throw $e;
            } else {
                throw new VictoriaBank\Exception(
                    'Authorization request to the payment gateway failed. Please contact '.$this->merchantUrl.' for further details'
                );
            }
        }
    }

    /**
     * @param mixed  $orderId  Merchant order ID
     * @param float  $amount   Transaction amount
     * @param string $rrn      Retrieval reference number from authorization response
     * @param string $intRef   Internal reference number from authorization response
     * @param string $currency Order currency: 3-character currency code
     *
     * @return mixed|void
     * @throws VictoriaBank\Exception
     */
    public function requestCompletion($orderId, $amount, $rrn, $intRef, $currency = null)
    {
        try {
            $request = new VictoriaBank\Completion\CompletionRequest(
                [
                    VictoriaBank\Completion\CompletionRequest::TERMINAL => $this->terminal,
                    VictoriaBank\Completion\CompletionRequest::ORDER => $this->normalizeOrderId($orderId),
                    VictoriaBank\Completion\CompletionRequest::AMOUNT => $this->normalizeAmount($amount),
                    VictoriaBank\Completion\CompletionRequest::CURRENCY => $currency ? $currency : $this->defaultCurrency,
                    VictoriaBank\Completion\CompletionRequest::TIMESTAMP => $this->getTransactionTimestamp(),
                    VictoriaBank\Completion\CompletionRequest::NONCE => $this->generateNonce(),
                    VictoriaBank\Completion\CompletionRequest::RRN => $rrn,
                    VictoriaBank\Completion\CompletionRequest::INT_REF => $intRef,
                ], $this->debug
            );

            return $request->request();
        } catch (VictoriaBank\Exception $e) {
            if ($this->debug) {
                throw $e;
            } else {
                throw new VictoriaBank\Exception(
                    'Completion request to the payment gateway failed. Please contact '.$this->merchantUrl.' for further details.'.$e->getMessage()
                );
            }
        }
    }

    /**
     * @param mixed  $orderId  Merchant order ID
     * @param float  $amount   Transaction amount
     * @param string $rrn      Retrieval reference number from authorization response
     * @param string $intRef   Internal reference number from authorization response
     * @param string $currency Order currency: 3-character currency code
     *
     * @return mixed|void
     * @throws VictoriaBank\Exception
     */
    public function requestReversal($orderId, $amount, $rrn, $intRef, $currency = null)
    {
        try {
            $request = new VictoriaBank\Reversal\ReversalRequest(
                [
                    VictoriaBank\Reversal\ReversalRequest::TERMINAL => $this->terminal,
                    VictoriaBank\Reversal\ReversalRequest::ORDER => $this->normalizeOrderId($orderId),
                    VictoriaBank\Reversal\ReversalRequest::AMOUNT => $this->normalizeAmount($amount),
                    VictoriaBank\Reversal\ReversalRequest::CURRENCY => $currency ? $currency : $this->defaultCurrency,
                    VictoriaBank\Reversal\ReversalRequest::TIMESTAMP => $this->getTransactionTimestamp(),
                    VictoriaBank\Reversal\ReversalRequest::NONCE => $this->generateNonce(),
                    VictoriaBank\Reversal\ReversalRequest::RRN => $rrn,
                    VictoriaBank\Reversal\ReversalRequest::INT_REF => $intRef,
                ], $this->debug
            );

            return $request->request();
        } catch (VictoriaBank\Exception $e) {
            if ($this->debug) {
                throw $e;
            } else {
                throw new VictoriaBank\Exception(
                    'Completion request to the payment gateway failed. Please contact '.$this->merchantUrl.' for further details.'.$e->getMessage()
                );
            }
        }
    }

    /**
     * Identifies the type of response object based on the received data over post from the bank
     *
     * @param array $post
     *
     * @return ResponseInterface
     * @throws VictoriaBank\Exception
     */
    public function getResponseObject(array $post)
    {
        if (!isset($post[VictoriaBank\Response::TRTYPE])) {
            throw new VictoriaBank\Exception('Invalid response data');
        }
        switch ($post[VictoriaBank\Response::TRTYPE]) {
            case VictoriaBank\Authorization\AuthorizationResponse::TRX_TYPE:
                return new VictoriaBank\Authorization\AuthorizationResponse($post);
                break;
            case VictoriaBank\Completion\CompletionResponse::TRX_TYPE:
                return new VictoriaBank\Completion\CompletionResponse($post);
                break;
            case VictoriaBank\Reversal\ReversalResponse::TRX_TYPE:
                return new VictoriaBank\Reversal\ReversalResponse($post);
                break;
            default:
                throw new VictoriaBank\Exception('No response object found for the provided data');
        }
    }

    /**
     * VictoriaBank accepts order ID not less than 6 characters long
     *
     * @param string|int $code
     *
     * @return string
     */
    static public function normalizeOrderId($code)
    {
        return sprintf('%06s', $code);
    }

    /**
     * VictoriaBank accepts order ID not less than 6 characters long
     *
     * @param string $code
     *
     * @return string
     */
    static public function deNormalizeOrderId($code)
    {
        return ltrim($code, '0');
    }

    /**
     * @param float $amount
     *
     * @return mixed
     */
    static public function normalizeAmount($amount)
    {
        return str_replace(',', '.', (string)$amount);
    }

    /**
     * Merchant nonce. Must be filled with 20-32 unpredictable random
     * bytes in hexadecimal format. Must be present if MAC is used
     *
     * @return string
     */
    protected function generateNonce()
    {
        return md5(mt_rand());
    }
}
