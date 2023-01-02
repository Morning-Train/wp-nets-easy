<?php namespace Morningtrain\WpNetsEasy\Enums;

class PaymentStatus {

    const INITIATED = 0;
    const CREATED = 1;
    const TERMINATED = 2;
    const CHECKOUT_COMPLETED = 3;
    const RESERVED = 4;
    const RESERVE_FAILED = 5;
    const PARTLY_CHARGE_CREATED = 6;
    const PARTLY_CHARGE_FAILED = 7;
    const CHARGE_CREATED = 8;
    const CHARGE_FAILED = 9;
    const PARTLY_REFUND_INITIATED = 10;
    const PARTLY_REFUND_FAILED = 11;
    const PARTLY_REFUND_COMPLETED = 12;
    const REFUND_INITIATED = 13;
    const REFUND_FAILED = 14;
    const REFUND_COMPLETED = 15;
    const CANCEL_CREATED = 16;
    const CANCEL_FAILED = 17;

}