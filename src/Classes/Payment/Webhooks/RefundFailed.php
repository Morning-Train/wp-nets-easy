<?php namespace Morningtrain\WpNetsEasy\Classes\Payment\Webhooks;

use Morningtrain\WpNetsEasy\Classes\Payment\Payment;
use \Morningtrain\WpNetsEasy\Classes\Payment\Webhook;
use Morningtrain\WpNetsEasy\Enums\PaymentStatus;

class RefundFailed extends Webhook {

    protected static string $eventName = 'payment.refund.failed';

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
            PaymentStatus::PARTLY_CHARGE_CREATED,
            PaymentStatus::CHARGE_CREATED,
            PaymentStatus::REFUND_COMPLETED,
            PaymentStatus::REFUND_INITIATED
        ])) {
            // TODO: Differentiate partly and fully refunds

            $payment->setPaymentStatus(PaymentStatus::REFUND_FAILED);
        }
    }
}