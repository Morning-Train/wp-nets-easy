<?php namespace Morningtrain\WpNetsEasy\Classes\Payment\Webhooks;

use Morningtrain\WpNetsEasy\Classes\Payment\Payment;
use \Morningtrain\WpNetsEasy\Classes\Payment\Webhook;
use Morningtrain\WpNetsEasy\Enums\PaymentStatus;

class ChargeFailed extends Webhook {

    protected static string $eventName = 'payment.charge.failed';

    public function handle()
    {
        $payment = Payment::getByPaymentId($this->getDataByKey('paymentId'));

        if(empty($payment)) {
            return new \WP_Error('no_payment', __('Invalid payment ID', 'morningtrain_nets_easy'), ['status' => 404]);
        }

        if(in_array($payment->getStatus(), [
            PaymentStatus::INITIATED,
            PaymentStatus::CREATED,
            PaymentStatus::CHECKOUT_COMPLETED,
            PaymentStatus::RESERVED,
        ])) {
            // TODO: Differentiate partly and fully charge

            $payment->setPaymentStatus(PaymentStatus::CHARGE_FAILED);
        }
    }
}