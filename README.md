# Omnipay: Sage Pay

**Sage Pay driver for the Omnipay PHP payment processing library**

[![Build Status](https://travis-ci.org/thephpleague/omnipay-sagepay.png?branch=master)](https://travis-ci.org/thephpleague/omnipay-sagepay)
[![Latest Stable Version](https://poser.pugx.org/omnipay/sagepay/version.png)](https://packagist.org/packages/omnipay/sagepay)
[![Total Downloads](https://poser.pugx.org/omnipay/sagepay/d/total.png)](https://packagist.org/packages/omnipay/sagepay)

[Omnipay](https://github.com/thephpleague/omnipay) is a framework agnostic, multi-gateway payment
processing library for PHP 5.3+. This package implements Sage Pay support for Omnipay.

## Installation

Omnipay is installed via [Composer](http://getcomposer.org/). To install, simply add it
to your `composer.json` file:

```json
{
    "require": {
        "omnipay/sagepay": "~2.0"
    }
}
```

And run composer to update your dependencies:

    $ curl -s http://getcomposer.org/installer | php
    $ php composer.phar update

## Basic Usage

The following gateways are provided by this package:

* SagePay_Direct
* SagePay_Server

For general usage instructions, please see the main [Omnipay](https://github.com/thephpleague/omnipay)
repository.

## Notification Handler

The `SagePay_Server` gateway uses a notification callback to receive the results of a payment or authorisation.
The URL for the notification handler is set in the authorize or payment message:

~~~php
// The response will be a redirect to the Sage Pay CC form.

$response = $gateway->purchase(array(
    'amount' => '9.99',
    'currency' => 'GBP',
    'card' => $card,
    'notifyUrl' => route('notify'), // Your application's route to your notication handler.
    'transactionId' => $transactionId,
    'description' => 'test',
    'items' => $items,
))->send();

// Before redirecting, save `$response->transactionReference()` in the database, indexed
// by `$transactionId`.
~~~

Your notification handler needs to do four things:

1. Look up the saved transaction in the database to retrieve the `transactionReference`.
2. Validate the signature of the recieved notification to protect against tampering.
3. Update your saved transaction with the results.
4. Respond to Sage Pay to indicate that you accept the result, reject the result or don't
   believe the notifcation was valid. Also tell Sage Pay where to send the user next.

This is a back-channel, so has no access to the end user's session.

The notify gateway is set up simply:

~~~php
$gateway = OmniPay\OmniPay::create('SagePay_Server');
$gateway->setVendor('your-vendor-name');
$gateway->setTestMode(true); // If testing
$request = $gateway->notify();
~~~

Your original `transactionId` is available to look up the transaction in the database:

~~~php
// Use this to look up the `$transactionReference` you saved:
$transactionId = $request->getTransactionId();
~~~

Now the signature can be checked:

~~~php
$request->setTransactionReference($transactionReference);

// Get the response ready for returning.
$response = $request->send();

if (!$request->checkSignature()) {
    // Respond to Sage Pay indicating we are not accepting anything about this message.
    // You might want to log `$request->getData()` first, for later analysis.

    $response->invalid($nextUrl, 'Signature not valid');
}
~~~

If you were not able to look up the transaction or the transaction is in the wrong state,
then indicate this with an error. Note an "error" is to indicate that although the notication
appears to be legitimate, you do not accept it or cannot handle it for any reason:

~~~php
$response->error($nextUrl, 'This transaction has already been paid');
~~~

If you accept the notification, then you can update your local records and let Sage Pay know:

~~~php
// All raw data - just log it for later analysis:
$request->getData();

// The payment or authorisation result:
// Result is $request::STATUS_COMPLETED, $request::STATUS_PENDING or $request::STATUS_FAILED
$request->getTransactionStatus();

// If you want more detail, look at the raw data.

// Not let Sage Pay know you have got it:
$response->confirm($nextUrl);
~~~

That's it. The `$nextUrl` is where you want Sage Pay to send the user to next.
It will often be the same URL whether the transaction was approved or not,
since the result will be safely saved in the database.

## Support

If you are having general issues with Omnipay, we suggest posting on
[Stack Overflow](http://stackoverflow.com/). Be sure to add the
[omnipay tag](http://stackoverflow.com/questions/tagged/omnipay) so it can be easily found.

If you want to keep up to date with release anouncements, discuss ideas for the project,
or ask more detailed questions, there is also a [mailing list](https://groups.google.com/forum/#!forum/omnipay) which
you can subscribe to.

If you believe you have found a bug, please report it using the [GitHub issue tracker](https://github.com/thephpleague/omnipay-sagepay/issues),
or better yet, fork the library and submit a pull request.
