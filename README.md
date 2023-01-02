# Morningtrain\WpNetsEasy

A Morningtrain package to simple handle NETS Easy payments.

## Table of Contents

- [Introduction](#introduction)
- [Getting Started](#getting-started)
    - [Installation](#installation)
- [Dependencies](#dependencies)
    - [illuminate/database](#illuminatedatabase)
- [Usage](#usage)
    - [Initializing package](#initializing-package)
    - [Create payment](#create-payment)
    - [Handle existing payment](#handle-existing-payment)
- [Credits](#credits)
- [Testing](#testing)
- [License](#license)

## Introduction

## Getting Started

To get started install the package as described below in [Installation](#installation).

To use the tool have a look at [Usage](#usage)

### Installation

Install with composer

```bash
composer require morningtrain/wp-nets-easy
```

## Dependencies

- [morningtrain/wp-route](https://packagist.org/packages/morningtrain/wp-route)
- [morningtrain/wp-database](https://packagist.org/packages/morningtrain/wp-database)
- [morningtrain/php-loader](https://packagist.org/packages/morningtrain/php-loader)

## Usage

### Initializing package

Initialize `\Morningtrain\WpNetsEasy\NetsEasy` with NETS Easy test or live secret key.

```php
\Morningtrain\WpNetsEasy\NetsEasy::init('live-secret-key-abcdefghijklmnopqrstuvwxyz123456789');
```

### Create payment

```php
use Morningtrain\WpNetsEasy\Classes\Payment\Payment;
use Morningtrain\WpNetsEasy\Classes\Payment\Customer;
use Morningtrain\WpNetsEasy\Classes\Payment\Address;
use Morningtrain\WpNetsEasy\Classes\Payment\Item;

// Create payment and set payment information and urls
$payment = Payment::create()
    ->setReference($orderId)
    ->setCustomer(
        Customer::create()
            ->setReference($customer->id)
            ->setEmail($customer->email)
            ->setPhone($customer->phone)
            ->setName($customer->firstName, $customer->lastName)
            ->setCompanyName($customer->companyName)
            ->setShippingAddress(
                Address::create()
                    ->setAddressLine1($customer->address1)
                    ->setAddressLine2($customer->address2)
                    ->setPostalCode($customer->zipCode)
                    ->setCity($customer->city())
            )
        )
    ->setTermsUrl(get_post_permalink($termsPageId))
    ->setReturnUrl(Route::route('payment-success', ['token' => $order->token]))
    ->setCancelUrl(Route::route('payment-cancel', ['token' => $order->token]));

// Add items to payments
foreach($order->items as $item) {
    $payment->addItem(
        Item::create($item->sku)
            ->setName($item->name)
            ->setQuantity($item->quantity)
            ->setUnitPriceInclusiveTax($item->price)
    );
}

// Persist payment in NETS Easy
$response = $payment->createRequest();

if(wp_remote_retrieve_response_code($response) !== 201) {
    // Error handling when something was wrong with the payment
    wp_redirect($checkoutUrl);
    exit();
}

// Save payment reference to order
$order->setPaymentId($payment->getPaymentId());

// Redirect to payment page
wp_redirect($payment->getPaymentPageUrl());
exit();
```

### Handle existing payment
When a payment requrest has been created, the payment reference will be saved to the database.

#### Get payment
Payment is a model implementet with Eloquent. 
To get payments you can use all methods from Eloquent (see [documentation](https://laravel.com/docs/9.x/eloquent#retrieving-models)).

You can use the custom method ```Payment::getByPaymentId($paymentId);```

```php
$payment = Payment::getByPaymentId($order->payment_id);
```

#### Terminate payment
To terminate payment, the customer must not have finished checkout.
You can use it on the cancel callback to avoid double payments later.

```php
$payment->terminate()
```

#### Check if payment is reserved

```php
$payment->isReserved()
```

#### Check if payment is charged

```php
$payment->isCharged()
```

#### Charge payment

```php
$payment->charge()
```

*NOTE: Partly charges is not implementet yet*

#### Refund payment

*NOTE: Refund and partly refund is not implementet yet*

### Create subscription

*NOTE: Subscriptions is not implementet yet*

### Handle existing subcripiton

*NOTE: Subscriptions is not implementet yet*

## Credits

- [Martin Schadegg Brønniche](https://github.com/mschadegg)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
