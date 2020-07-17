# Welcome to VictoriaBank Merchant e-Commerce Library 👋

[![GitHub issues](https://img.shields.io/github/issues/Fruitware/VictoriaBankGateway)](https://github.com/Fruitware/VictoriaBankGateway/issues)
[![Version](https://img.shields.io/packagist/v/fruitware/victoria-bank-gateway)](https://packagist.org/packages/fruitware/victoria-bank-gateway)
![Packagist](https://img.shields.io/packagist/l/fruitware/victoria-bank-gateway)
[![Donate](https://img.shields.io/badge/donate-PayPal-%2337a556)]( https://paypal.me/fruitware?locale.x=en_US)

---

> Packagist package (library) to give any php-based website an access to the interface of VictoriaBank (Republic Of Moldova) that merchant systems use to process credit card based e- commerce transactions using the standard CGI/WWW forms posting method. This interface transparently supports various cardholder authentication protocols such as 3-D Secure and Secure Code as well as legacy unauthenticated SSL commerce transactions.

#### 🏠 [Homepage](https://github.com/Fruitware/VictoriaBankGateway)

## Install

```sh
composer require fruitware/victoria-bank-gateway
```

### Requirements

* PHP >= 5.5
* OpenSSL >=0.9.8 

## Usage

### Step 1. Environment configuration (not required)

You can use one of the composer packages
```bash
composer require vlucas/phpdotenv
```

or

```bash
composer require symfony/dotenv
```

.env file

```dosini
# Merchant ID assigned by bank
VICTORIA_BANK_MERCHANT_ID=xxxxxxxxxxxxxxx

# Merchant Terminal ID assigned by bank 
VICTORIA_BANK_MERCHANT_TERMINAL=xxxxxxxx

# Merchant primary web site URL
VICTORIA_BANK_MERCHANT_URL='http://example.com'

# Merchant name (recognizable by cardholder)
VICTORIA_BANK_MERCHANT_NAME='Merchant company name'

# Merchant company registered office address
VICTORIA_BANK_MERCHANT_ADDRESS='Merchant address'

# Security options - provided by the bank
VICTORIA_BANK_SECURITY_SIGNATURE_FIRST='0001'
VICTORIA_BANK_SECURITY_SIGNATURE_PREFIX='A00B00C00D00EA864886F70D020505000410'
VICTORIA_BANK_SECURITY_SIGNATURE_PADDING='00'

# Merchant public rsa key
VICTORIA_BANK_MERCHANT_PUBLIC_KEY=public.pem

# Merchant private rsa key
VICTORIA_BANK_MERCHANT_PRIVATE_KEY=private.pem

# The public part of the bank key that P_SIGN is encrypted in the response in PEM format.
VICTORIA_BANK_MERCHANT_BANK_PUBLIC_KEY=victoria_pub.pem

# Default Merchant shop timezone
# Used to calculate the timezone offset sent to VictoriaBank
VICTORIA_BANK_MERCHANT_TIMEZONE_NAME='Europe/Chisinau'

# Merchant shop 2-character country code. 
# Must be provided if merchant system is located 
# in a country other than the gateway server's country. 
VICTORIA_BANK_MERCHANT_COUNTRY_CODE=MD

# Default currency for all operations: 3-character currency code 
VICTORIA_BANK_MERCHANT_DEFAULT_CURRENCY=MDL

# Default forms language
# By default are available forms in en, ro, ru. 
# If need forms in another languages please contact gateway
# administrator
VICTORIA_BANK_MERCHANT_DEFAULT_LANGUAGE=ro
```

### Step 2. Init Gateway client

#### Init Gateway client through configureFromEnv method

```php
<?php

use Fruitware\VictoriaBankGateway\VictoriaBankGateway;

$victoriaBankGateway = new VictoriaBankGateway();

$certDir = '/path/to/cert/dir';
$victoriaBankGateway
    ->configureFromEnv($certDir)
;
```

#### Init Gateway client manually

You can reproduce implementation of the configureFromEnv() method


### Step 3. Request payment authorization - redirects to the banks page

```php
<?php

use Fruitware\VictoriaBankGateway\VictoriaBankGateway;
$backRefUrl = getenv('VICTORIA_BANK_MERCHANT_URL').'/after-payment/';

/** @var VictoriaBankGateway $victoriaBankGateway */
$victoriaBankGateway
    ->requestAuthorization($orderId = 1, $amount = 1, $backRefUrl, $currency = null, $description = null, $clientEmail = null, $language = null)
;
```

### Step 4. Receive bank responses - all bank responses are asynchronous server to server and are handled by same URI

```php
<?php

use Fruitware\VictoriaBankGateway\VictoriaBankGateway;
use Fruitware\VictoriaBankGateway\VictoriaBank\Exception;
use Fruitware\VictoriaBankGateway\VictoriaBank\Response;
use Fruitware\VictoriaBankGateway\VictoriaBank\AuthorizationResponse;

/** @var VictoriaBankGateway $victoriaBankGateway */
$bankResponse = $victoriaBankGateway->getResponseObject($_POST);

if (!$bankResponse->isValid()) {
    throw new Exception('Invalid bank Auth response');
}

switch ($bankResponse::TRX_TYPE) {
    case VictoriaBankGateway::TRX_TYPE_AUTHORIZATION:
        $amount         = $bankResponse->{Response::AMOUNT};
        $bankOrderCode  = $bankResponse->{Response::ORDER};
        $rrn            = $bankResponse->{Response::RRN};
        $intRef         = $bankResponse->{Response::INT_REF};

        #
        # You must save $rrn and $intRef from the response here for reversal requests
        #

        # Funds locked on bank side - transfer the product/service to the customer and request completion
        $victoriaBankGateway->requestCompletion($bankOrderCode, $amount, $rrn, $intRef, $currency = null);
        break;

    case VictoriaBankGateway::TRX_TYPE_COMPLETION:
        # Funds successfully transferred on bank side
        break;

    case VictoriaBankGateway::TRX_TYPE_REVERSAL:
        # Reversal successfully applied on bank size
        break;

    default:
        throw new Exception('Unknown bank response transaction type');
}
```

### Step 5. Request reversal (refund)

```$rrn``` and ```$intRef``` must be saved on the step 4

```php
<?php

use Fruitware\VictoriaBankGateway\VictoriaBankGateway;

/** @var VictoriaBankGateway $victoriaBankGateway */
$victoriaBankGateway
    ->requestReversal($orderId = 1, $amount = 1, $rrn = 'xxx', $intRef = 'yyy', $currency = null)
;
```

## Author

👤 Lovely handcrafted by **Fruitware team**

* Twitter: [@fruitware](https://twitter.com/fruitware)
* Github: [@fruitware](https://github.com/fruitware)

## 🤝 Contributing

Contributions, issues and feature requests are welcome!<br />Feel free to check [issues page](https://github.com/Fruitware/VictoriaBankGateway/issues).

## Show your support 

Give a ⭐ if this project helped you!

[![Donate](https://img.shields.io/badge/donate-PayPal-%2337a556)]( https://paypal.me/fruitware?locale.x=en_US)
