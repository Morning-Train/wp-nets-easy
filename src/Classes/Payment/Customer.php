<?php namespace Morningtrain\WpNetsEasy\Classes\Payment;

use Morningtrain\WpNetsEasy\Enums\PhoneCountryCode;
use JsonSerializable;

class Customer implements JsonSerializable {

    protected ?string $reference = null;
    protected ?string $email = null;
    protected ?string $firstName = null;
    protected ?string $lastName = null;
    protected ?string $companyName = null;
    protected ?string $phoneNumber = null;
    protected ?string $phoneCountryCode = null;
    protected ?Address $shippingAddress = null;

    public static function create() : static
    {
        return new static();
    }

    public function setReference(string $refrence) : static
    {
        $this->reference = $refrence;

        return $this;
    }

    public function setEmail(string $email) : static
    {
        $this->email = $email;

        return $this;
    }

    public function setFirstName(string $firstName) : static
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function setLastName(string $lastName) : static
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function setName(string $firstName, string $lastName) : static
    {
        $this->setFirstName($firstName);
        $this->setLastName($lastName);

        return $this;
    }

    /**
     * @param string $phoneNumber
     * @param string $phoneCountryCode Use \Morningtrain\WpNetsEasy\Enums\PhoneCountryCode for correct country code
     * @return $this
     */
    public function setPhone(string $phoneNumber, string $phoneCountryCode = PhoneCountryCode::DK) : static
    {
        $this->setPhoneNumber($phoneNumber);
        $this->phoneCountryCode = $phoneCountryCode;

        return $this;
    }

    public function setPhoneNumber(string $phoneNumber) : static
    {
        $this->phoneNumber = $phoneNumber;

        return $this;
    }

    /**
     * Phone contry code
     * @param string $phoneCountryCode Use \Morningtrain\WpNetsEasy\Enums\PhoneCountryCode for correct country code
     * @return $this
     */
    public function setPhoneCountryCode(string $phoneCountryCode) : static
    {
        $this->phoneCountryCode = $phoneCountryCode;

        return $this;
    }

    public function setCompanyName(string $companyName) : static
    {
        $this->companyName = $companyName;

        return $this;
    }

    public function setShippingAddress(Address $shippingAddress) : static
    {
        $this->shippingAddress = $shippingAddress;

        return $this;
    }

    public function getReference() : ?string
    {
        return $this->reference;
    }

    public function getEmail() : ?string
    {
        return $this->email;
    }

    public function getShippingAddress() : ?Address
    {
        return $this->shippingAddress;
    }

    public function getPhoneNumber() : ?string
    {
        return $this->phoneNumber;
    }

    public function getPhoneCountryCode() : ?string
    {
        return $this->phoneCountryCode;
    }

    public function getPhone() : ?array
    {
        if(empty($this->getPhoneNumber()) || empty($this->getPhoneCountryCode())) {
            return null;
        }

        return [
            'prefix' => $this->getPhoneCountryCode(),
            'number' => $this->getPhoneNumber()
        ];
    }

    public function getCompanyName() : ?string
    {
        return $this->companyName;
    }

    public function getFirstName() : ?string
    {
        return $this->firstName;
    }

    public function getLastName() : ?string
    {
        return $this->lastName;
    }

    public function getName() : ?array
    {
        $name = [];

        if(!empty($this->getFirstName())) {
            $name['firstName'] = $this->getFirstName();
        }

        if(!empty($this->getLastName())) {
            $name['lastName'] = $this->getLastName();
        }

        if(empty($name)) {
            return null;
        }

        return $name;
    }

    public function isCompany() : bool
    {
        return !empty($this->getCompanyName());
    }

    /**
     * See https://developers.nets.eu/nets-easy/en-EU/api/payment-v1/#v1-payments-post-body-checkout-consumer
     * @return mixed
     */
    public function jsonSerialize() : mixed
    {
        $customer = [];

        if(!empty($this->getReference())) {
            $customer['reference'] = $this->getReference();
        }

        if(!empty($this->getEmail())) {
            $customer['email'] = $this->getEmail();
        }

        if(!empty($this->getShippingAddress())) {
            $customer['shippingAddress'] = $this->getShippingAddress();
        }

        if(!empty($this->getPhone())) {
            $customer['phone'] = $this->getPhone();
        }

        if($this->isCompany()) {
            $customer['company'] = [
                'name' => $this->getCompanyName()
            ];

            if(!empty($this->getName())) {
                $customer['company']['contact'] = $this->getName();
            }
        } else {
            if(!empty($this->getName())) {
                $customer['privatePerson'] = $this->getName();
            }
        }

        return $customer;
    }
    
}