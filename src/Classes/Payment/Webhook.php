<?php namespace Morningtrain\WpNetsEasy\Classes\Payment;

use DateTime;
use Exception;

abstract class Webhook {

    protected static string $eventName;

    protected string $id;
    protected int $merchantId;
    protected string $timeStamp;
    protected ?object $data;

    public function __construct(object $payload)
    {
        $this->id = $payload->id;
        $this->merchantId = $payload->merchantId ?? $payload->merchantNumber;
        $this->timeStamp = $payload->timestamp;
        $this->data = $payload->data;
    }

    public static function getEventName() : string
    {
        return static::$eventName;
    }

    public function getData() : ?object
    {
        return $this->data;
    }

    public function getDataByKey(string $key, mixed $default = null) : mixed
    {
        $data = $this->getData();

        if(empty($data)) {
            return $default;
        }

        $keyParts = explode('.', $key);

        foreach($keyParts as $keyPart) {
            if(!isset($data->$keyPart)) {
                return $default;
            }

            $data = $data->$keyPart;
        }

        return $data;
    }

    public function getId() : string
    {
        return $this->id;
    }

    public function getMerchantId() : int
    {
        return $this->merchantId;
    }

    public function getDateTime() : ?DateTime
    {
        $date = $this->timeStamp;

        if(empty($date)) {
            return null;
        }

        try {
            return new DateTime($date);
        } catch (Exception $e) {
            return null;
        }
    }

    public function getPaymentId() : string
    {
        return $this->getDataByKey('paymentId');
    }

    public function getPayment() : ?Payment
    {
        return Payment::getByPaymentId($this->getPaymentId());
    }

    public function isHandled() : bool
    {
        $payment = $this->getPayment();

        if(empty($payment)) {
            return false;
        }

        return in_array($this->getId(), $payment->getWebhookIds());
    }

    public function handled()
    {
        $payment = $this->getPayment();

        if(empty($payment)) {
            return;
        }

        $payment->setWebhookId($this->getId());
    }

    public static function getUrl() : string
    {
        return WebhookHandler::getUrl(['webhook' => static::getEventName()]);
    }

    public static function getWebhookArray() : mixed
    {
        return [
            'eventName' => static::getEventName(),
            'url' => static::getUrl(),
            'authorization' => WebhookHandler::getAuthKey(),
        ];
    }

    public static function register()
    {
        WebhookHandler::registerWebhook(static::class);
    }

    abstract function handle();
}