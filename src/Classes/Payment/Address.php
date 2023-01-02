<?php namespace Morningtrain\WpNetsEasy\Classes\Payment;

use JsonSerializable;
use Morningtrain\WpNetsEasy\Enums\CountryCode;


class Address implements JsonSerializable {
    
    protected ?string $addressLine1;
    protected ?string $addressLine2;
    protected ?string $postalCode;
    protected ?string $city;
    protected string $country = CountryCode::DK;

    public static function create() : static
    {
        return new static();
    }
    
    public function setAddressLine1(string $addressLine1) : static
    {
        $this->addressLine1 = $addressLine1;
        
        return $this;
    }
    
    public function setAddressLine2(string $addressLine2) : static
    {
        $this->addressLine2 = $addressLine2;
        
        return $this;
    }

    public function setPostalCode(string $postalCode) : static
    {
        $this->postalCode = $postalCode;
        
        return $this;
    }
    
    public function setCity(string $city) : static
    {
        $this->city = $city;
        
        return $this;
    }

    /**
     * Three letter country code
     * @param string $countryCode Use Morningtrain\WpNetsEasy\Enums\CountryCode to converte from two letter to tree letter
     * @return $this
     */
    public function setCounty(string $countryCode) : static
    {
        $this->country = $countryCode;
        
        return $this;
    }

    public function getAddressLine1() : ?string
    {
        return $this->addressLine1;
    }

    public function getAddressLine2() : ?string
    {
        return $this->addressLine2;
    }

    public function getPostalCode() : ?string
    {
        return $this->postalCode;
    }

    public function getCity() : ?string
    {
        return $this->city;
    }

    public function getCountry() : string
    {
        return $this->country;
    }

    /**
     * see https://developers.nets.eu/nets-easy/en-EU/api/payment-v1/#v1-payments-post-body-checkout-consumer-shippingaddress
     * @return mixed
     */
    public function jsonSerialize() : mixed
    {
        $address = [];

        if(!empty($this->getAddressLine1())) {
            $address['addressLine1'] = $this->getAddressLine1();
        }

        if(!empty($this->getAddressLine2())) {
            $address['addressLine2'] = $this->getAddressLine2();
        }

        if(!empty($this->getPostalCode())) {
            $address['postalCode'] = $this->getPostalCode();
        }

        if(!empty($this->getCity())) {
            $address['city'] = $this->getCity();
        }

        if(!empty($this->getCountry())) {
            $address['country'] = $this->getCountry();
        }

        return $address;
    }
}