<?php namespace Morningtrain\WpNetsEasy\Classes\Payment;

use JsonSerializable;
use Morningtrain\WpNetsEasy\Services\IntegerService;

class Item implements JsonSerializable {

    protected string $reference;
    protected ?string $name = null;
    protected float $quantity = 1;
    protected string $unit = 'stk.';
    protected ?float $unitPrice = 0;
    protected ?float $unitPriceInclusiveTax = null;
    protected float $taxRate = 25;

    public function __construct(string $reference)
    {
        $this->setReference($reference);
    }

    public function setReference(string $reference) : static
    {
        $this->reference = $reference;

        return $this;
    }

    public function setName(string $name) : static
    {
        $this->name = $name;

        return $this;
    }

    public function setQuantity(float $quantity) : static
    {
        $this->quantity = $quantity;

        return $this;
    }

    public function setUnit(string $unit) : static
    {
        $this->unit = $unit;

        return $this;
    }

    public function setUnitPrice(float $unitPrice) : static
    {
        $this->unitPrice = $unitPrice;
        $this->unitPriceInclusiveTax = null;

        return $this;
    }

    public function setUnitPriceInclusiveTax(float $unitPriceInclusiveTax) : static
    {
        $this->unitPriceInclusiveTax = $unitPriceInclusiveTax;
        $this->unitPrice = null;

        return $this;
    }

    public function setTaxRate(float $taxRate) : static
    {
        $this->taxRate = $taxRate;

        return $this;
    }

    public function getReference() : string
    {
        return $this->reference;
    }

    public function getName() : string
    {
        if(empty($this->name)) {
            return $this->getReference();
        }

        return (string) $this->name;
    }

    public function getQuantity() : float
    {
        return $this->quantity;
    }

    public function getUnit() : string
    {
        return $this->unit;
    }

    public function getUnitPrice() : float
    {
        if($this->unitPriceInclusiveTax == null) {
            return (float) $this->unitPrice;
        }

        return $this->unitPriceInclusiveTax / (1 + ($this->getTaxRate()/100));
    }

    public function getUnitPriceInclusiveTax() : float
    {
        if($this->unitPrice == null) {
            return (float) $this->unitPriceInclusiveTax;
        }

        return $this->unitPrice * (1 + ($this->getTaxRate()/100));
    }

    public function getTaxRate() : float
    {
        return $this->taxRate;
    }

    public function getTaxAmount() : float
    {
        return $this->getNetTotalAmount() * $this->getTaxRate() / 100;
    }

    public function getGrossTotalAmount() : float
    {
        return $this->getNetTotalAmount() + $this->getTaxAmount();
    }

    public function getNetTotalAmount() : float
    {
        return $this->getUnitPrice() * $this->getQuantity();
    }

    public static function create(string $reference) : static
    {
        return new static($reference);
    }

    public static function createFromArray($array) : ?static
    {
        if(empty($array['reference'])) {
            return null;
        }

        $paymentItem = static::create($array['reference']);

        $arrayKeys = [
            'name' => 'setName',
            'quantity' => 'setQuantity',
            'unit' => 'setUnit',
            'unitPrice' => 'setUnitPrice',
            'taxRate' => 'setTaxRate'
        ];

        foreach($arrayKeys as $arrayKey => $function) {
            if(isset($array[$arrayKey])) {
                $paymentItem->{$function}($array[$arrayKey]);
            }
        }

        return $paymentItem;
    }

    /**
     * See https://developers.nets.eu/nets-easy/en-EU/api/payment-v1/#v1-payments-post-body-order-items
     * @return mixed
     */
    public function jsonSerialize() : mixed
    {
        return [
            'reference' => $this->getReference(),
            'name' => $this->getName(),
            'quantity' => $this->getQuantity(),
            'unit' => $this->getUnit(),
            'unitPrice' => IntegerService::convertToOneHundredthInt($this->getUnitPrice()),
            'taxRate' => IntegerService::convertToOneHundredthInt($this->getTaxRate()),
            'taxAmount' => IntegerService::convertToOneHundredthInt($this->getTaxAmount()),
            'grossTotalAmount' => IntegerService::convertToOneHundredthInt($this->getGrossTotalAmount()),
            'netTotalAmount' => IntegerService::convertToOneHundredthInt($this->getNetTotalAmount())
        ];
    }
}