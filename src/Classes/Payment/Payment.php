<?php namespace Morningtrain\WpNetsEasy\Classes\Payment;

use DateTime;
use JsonSerializable;
use Morningtrain\WpNetsEasy\Classes\NetsEasyClient;
use Morningtrain\WpNetsEasy\Enums\Currency;
use Morningtrain\WpNetsEasy\Enums\Language;
use Morningtrain\WpNetsEasy\Enums\PaymentStatus;
use Morningtrain\WpNetsEasy\Models\Payment\PaymentReference;
use Morningtrain\WpNetsEasy\Services\IntegerService;
use Exception;
use WP_Error;

class Payment implements JsonSerializable {

    protected ?NetsEasyClient $netsEasyClient;

    /**
     * @var Item[]
     */
    protected array $items = [];
    protected string $currency = Currency::DKK;
    protected string $reference = '';
    protected Customer $customer;
    protected string $termsUrl;
    protected string $returnUrl;
    protected string $cancelUrl;
    protected bool $autoCharge = false;
    protected string $language = Language::da;
    protected ?PaymentReference $paymentReference = null;
    protected ?string $paymentPageUrl = null;

    protected ?object $fetchedData = null;


    public static function create($netsEasyClient = null) : static
    {
        $instance = new static($netsEasyClient);

        return $instance;
    }

    public function __construct($netsEasyClient = null)
    {
        $this->setNetsEasyClient($netsEasyClient);
    }

    public function createRequest() : WP_Error|array
    {
        $request = $this->getNetsEasyClient()->post('v1/payments', $this);

        if(wp_remote_retrieve_response_code($request) === 201) {
            $body = json_decode(wp_remote_retrieve_body($request));
            $this->setPaymentId($body->paymentId);
            $this->setPaymentPageUrl($body->hostedPaymentPageUrl);
            $this->setPaymentStatus(PaymentStatus::CREATED);
        }

        return $request;
    }

    public function terminate() : WP_Error|array
    {
        $request = $this->getNetsEasyClient()->put("v1/payments/{$this->getPaymentId()}/terminate");

        if(wp_remote_retrieve_response_code($request) === 204) {
            $this->setPaymentStatus(PaymentStatus::TERMINATED);

            $this->fetch();
        }

        return $request;
    }

    public function cancel() : WP_Error|array
    {
        $request = $this->getNetsEasyClient()->post("v1/payments/{$this->getPaymentId()}/cancels");

        if(wp_remote_retrieve_response_code($request) === 204) {
            $this->setPaymentStatus(PaymentStatus::CANCEL_CREATED);

            $this->fetch();
        }

        return $request;
    }

    public function charge() : WP_Error|array
    {
        $request = $this->getNetsEasyClient()->post("v1/payments/{$this->getPaymentId()}/charges", [
            'amount' => IntegerService::convertToOneHundredthInt($this->getAmount())
        ]);

        if(wp_remote_retrieve_response_code($request) === 201) {
            $this->setPaymentStatus(PaymentStatus::CHARGE_CREATED);

            $this->fetch();
        }

        return $request;
    }

    public function chargePartly(array $items) {

    }


    public function addItem(Item $item) : static
    {
        $this->items[] = $item;

        return $this;
    }

    public function setCurrency(string $currency) : static
    {
        $this->currency = $currency;

        return $this;
    }

    public function setReference(string $reference) : static
    {
        $this->reference = $reference;

        return $this;
    }

    public function setCustomer(Customer $customer) : static
    {
        $this->customer = $customer;

        return $this;
    }

    public function setTermsUrl(string $termsUrl) : static
    {
        $this->termsUrl = $termsUrl;

        return $this;
    }

    /**
     * @param string $languageCode Use
     * @return $this
     */
    public function setLanguage(string $languageCode) : static
    {
        $this->language = $languageCode;

        return $this;
    }

    public function setReturnUrl(string $returnUrl) : static
    {
        $this->returnUrl = $returnUrl;

        return $this;
    }

    public function setCancelUrl(string $cancelUrl) : static
    {
        $this->cancelUrl = $cancelUrl;

        return $this;
    }

    public function autoCharge() : static
    {
        return $this->setAutoCharge(true);
    }

    public function setAutoCharge(bool $autoCharge) : static
    {
        $this->autoCharge = $autoCharge;

        return $this;
    }

    public function setNetsEasyClient(?NetsEasyClient $netsEasyClient) : static
    {
        $this->netsEasyClient = $netsEasyClient;

        return $this;
    }

    protected function setPaymentId(string $paymenId)
    {
        $paymentReference = $this->getOrCreatePaymentReference($paymenId);
        $paymentReference->payment_id = $paymenId;
        $paymentReference->save();
        $paymentReference->refresh();
    }

    public function setPaymentStatus(int $paymentStatus)
    {
        $paymentReference = $this->getPaymentReference();

        if(empty($paymentReference)) {
            return;
        }

        $paymentReference->status = $paymentStatus;
        $paymentReference->save();
        $paymentReference->refresh();
    }

    protected function setPaymentPageUrl(string $paymentPageUrl)
    {
        $this->paymentPageUrl = $paymentPageUrl;
    }

    public function setPaymentReference(PaymentReference $paymentReference) : static
    {
        $this->paymentReference = $paymentReference;

        return $this;
    }

    protected function getPaymentReference() : ?PaymentReference
    {
        return $this->paymentReference;
    }

    protected function getOrCreatePaymentReference(string $paymentId) : PaymentReference
    {
        $this->setPaymentReference(PaymentReference::firstOrCreate(['payment_id' => $paymentId]));

        return $this->paymentReference;
    }

    /**
     * @return Item[]
     */
    public function getItems() : array
    {
        return $this->items;
    }

    public function getAmount() : float
    {
        if(!$this->isCreated()) {
            return array_reduce($this->getItems(), function (float $carry, Item $item) {
                return $carry + $item->getGrossTotalAmount();
            }, 0);
        }

        return IntegerService::convertFromOneHundredthInt($this->getFetchedDataByKey('payment.orderDetails.amount', 0));
    }

    public function getCurrency() : string
    {
        return $this->currency;
    }

    public function getReference() : string
    {
        return $this->reference;
    }

    public function getCustomer() : Customer
    {
        return $this->customer;
    }

    public function getAutoCharge() : bool
    {
        return $this->autoCharge;
    }

    public function getTermsUrl() : string
    {
        return $this->termsUrl;
    }

    public function getReturnUrl() : string
    {
        return $this->returnUrl;
    }

    public function getCancelUrl() : string
    {
        return $this->cancelUrl;
    }

    public function getNetsEasyClient() : NetsEasyClient
    {
        if(empty($this->netsEasyClient)) {
            return NetsEasyClient::getGlobalInstance();
        }

        return $this->netsEasyClient;
    }

    public function getPaymentId() : ?string
    {
        $paymentReference = $this->getPaymentReference();

        if(empty($this->paymentReference)) {
            return null;
        }

        return $paymentReference->payment_id;
    }

    public function getPaymentStatus() : ?int
    {
        $paymentReference = $this->getPaymentReference();

        if(empty($this->paymentReference)) {
            return null;
        }

        return $paymentReference->status;
    }

    public function getWebhookIds() : array
    {
        $paymentReference = $this->getPaymentReference();

        if(empty($paymentReference) || empty($paymentReference->webhook_ids)) {
            return [];
        }

        return $paymentReference->webhook_ids;
    }

    public function setWebhookId(string $webhookId) : static
    {
        $paymentReference = $this->getPaymentReference();

        if(empty($this->paymentReference)) {
            return $this;
        }

        if(!is_array($paymentReference->webhook_ids)) {
            $paymentReference->webhook_ids = [];
        }

        $webhookIds = is_array($paymentReference->webhook_ids) ? $paymentReference->webhook_ids : [];

        $webhookIds[] = $webhookId;

        $paymentReference->webhook_ids = $webhookIds;
        $paymentReference->save();
        $paymentReference->refresh();

        return $this;
    }

    public function getPaymentPageUrl() : ?string
    {
        if(!$this->isCreated() || !empty($this->paymentPageUrl)) {
            return $this->paymentPageUrl;
        }

        return $this->getFetchedDataByKey('payment.checkout.url', $this->paymentPageUrl);
    }

    protected function getFetchedData() : ?object
    {
        if(empty($this->fetchedData)) {
            $this->fetch();
        }

        return $this->fetchedData;
    }

    /**
     * Get fetched data by key. See https://developers.nets.eu/nets-easy/en-EU/api/payment-v1/#v1-payments-paymentid-get-responses-200-payment
     *
     * @param string $key Use . as separator for layers (ex. payment.summary.reservedAmount)
     * @param mixed|null $default
     * @return mixed
     */
    protected function getFetchedDataByKey(string $key, mixed $default = null) : mixed
    {
        $fetchedData = $this->getFetchedData();

        if(empty($fetchedData)) {
            return $default;
        }

        $keyParts = explode('.', $key);

        foreach($keyParts as $keyPart) {
            if(!isset($fetchedData->$keyPart)) {
                return $default;
            }

            $fetchedData = $fetchedData->$keyPart;
        }

        return $fetchedData;
    }

    public function getReservedAmount() : float
    {
        return IntegerService::convertFromOneHundredthInt($this->getFetchedDataByKey('payment.summary.reservedAmount', 0));
    }

    public function getChargedAmount() : float
    {
        return IntegerService::convertFromOneHundredthInt($this->getFetchedDataByKey('payment.summary.chargedAmount', 0));
    }

    public function getRefundedAmount() : float
    {
        return IntegerService::convertFromOneHundredthInt($this->getFetchedDataByKey('payment.summary.refundedAmount', 0));
    }

    public function getCancelledAmount() : float
    {
        return IntegerService::convertFromOneHundredthInt($this->getFetchedDataByKey('payment.summary.cancelledAmount', 0));
    }

    public function getTerminatedDateTime() : ?DateTime
    {
        $date = $this->getFetchedDataByKey('payment.terminated');

        if(empty($date)) {
            return null;
        }

        try {
            return new DateTime($date);
        } catch (Exception $e) {
            return null;
        }
    }

    public function getStatus() : int
    {
        if(empty($this->getPaymentReference())) {
            return PaymentStatus::INITIATED;
        }

        return $this->getPaymentReference()->status;
    }

    /**
     * Is payment created in Nets Easy
     * @return bool
     */
    public function isCreated() : bool
    {
        return $this->getStatus() !== PaymentStatus::INITIATED;
    }

    /**
     * Is payment reserves
     * @return bool
     */
    public function isReserved() : bool
    {
        if(in_array($this->getStatus(), [PaymentStatus::RESERVED, PaymentStatus::CHARGE_CREATED])) {
            return true;
        }

        return $this->getReservedAmount() === $this->getAmount();
    }

    /**
     * Is payment fully charged
     * @return bool
     */
    public function isCharged() : bool
    {
        if($this->getStatus() === PaymentStatus::CHARGE_CREATED) {
            return true;
        }

        return $this->getChargedAmount() === $this->getAmount();
    }

    /**
     * Is payment fully refunded
     * @return bool
     */
    public function isRefunded() : bool
    {
        if($this->getStatus() === PaymentStatus::REFUND_COMPLETED) {
            return true;
        }

        return $this->getRefundedAmount() === $this->getAmount();
    }

    /**
     * Is payment cancelled
     * @return bool
     */
    public function isCancelled() : bool
    {
        if($this->getStatus() === PaymentStatus::CANCEL_CREATED) {
            return true;
        }

        return $this->getCancelledAmount() === $this->getAmount();
    }

    /**
     * Is payment terminated
     * @return bool
     */
    public function isTerminated() : bool
    {
        if($this->getStatus() === PaymentStatus::TERMINATED) {
            return true;
        }

        return !empty($this->getTerminatedDateTime());
    }

    /**
     * Fetch data from NETS EASY and save it in $this->fetchedData
     * @return WP_Error|array WP_Error or WP request array
     */
    public function fetch() : WP_Error|array
    {
        if(empty($this->getPaymentId())) {
            return new WP_Error('empty_payment_id', __('The payment ID is empty', 'morningtrain_nets_easy'));
        }

        $request = $this->getNetsEasyClient()->get('v1/payments/' . $this->getPaymentId());

        if(wp_remote_retrieve_response_code($request) === 200) {
            $this->fetchedData = json_decode(wp_remote_retrieve_body($request));
        }

        return $request;

    }

    public function jsonSerialize() : mixed
    {
        // Order
        $order = [
            'items' => $this->getItems(),
            'amount' => IntegerService::convertToOneHundredthInt($this->getAmount()),
            'currency' => $this->getCurrency(),
        ];

        if(!empty($this->getReference())) {
            $order['reference'] = $this->getReference();
        }

        // Checkout
        $checkout = [
            'integrationType' => 'HostedPaymentPage',
            'returnUrl' => $this->getReturnUrl(),
            'cancelUrl' => $this->getCancelUrl(),
            'termsUrl' => $this->getTermsUrl(),
            'charge' => $this->getAutoCharge(),
        ];

        if(!empty($this->getCustomer())) {
            $checkout['merchantHandlesConsumerData'] = true;
            $checkout['consumer'] = $this->getCustomer();
        }


        $webhooks = [];

        foreach(WebhookHandler::getWebhooks() as $webhookClass) {
            $webhooks[] = $webhookClass::getWebhookArray();
        }

        return [
            'order' => $order,
            'checkout' => $checkout,
            'notifications' => [
                'webHooks' => $webhooks
            ],
        ];
    }

    public static function getByPaymentId(string $paymentId) : ?static
    {
        $paymentReference = PaymentReference::where('payment_id', $paymentId)->first();

        if(empty($paymentReference)) {
            return null;
        }

        return $paymentReference->getPayment();
    }
}