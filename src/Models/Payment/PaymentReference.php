<?php namespace Morningtrain\WpNetsEasy\Models\Payment;

use Illuminate\Database\Eloquent\Model;
use Morningtrain\WpNetsEasy\Classes\Payment\Payment;

class PaymentReference extends Model {

    protected $table = 'payments';

    protected $fillable = [
        'payment_id'
    ];

    protected $casts = [
        'webhook_ids' => 'array'
    ];

    protected $guarded = [];

    public function getPayment() : Payment
    {
        $payment = new Payment();

        $payment->setPaymentReference($this);

        return $payment;
    }
}