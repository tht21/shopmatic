<?php


namespace App\Integrations;

use App\Models\Account;
use App\Models\Customer;

class TransformedAddress
{

    public $company;
    public $name;
    public $address1;
    public $address2;
    public $address3;
    public $address4;
    public $address5;
    public $city;
    public $postcode;
    public $state;
    public $country;
    public $phoneNumber;
    public $email;

    /**
     * TransformedAddress constructor.
     *
     * @param $company
     * @param $name
     * @param $address1
     * @param $address2
     * @param $address3
     * @param $address4
     * @param $address5
     * @param $city
     * @param $postcode
     * @param $state
     * @param $country
     * @param $phoneNumber
     * @param null $email
     */
    public function __construct($company, $name, $address1, $address2, $address3, $address4, $address5, $city, $postcode, $state, $country, $phoneNumber, $email = null)
    {
        $this->company = $company;
        $this->name = $name;
        $this->address1 = $address1;
        $this->address2 = $address2;
        $this->address3 = $address3;
        $this->address4 = $address4;
        $this->address5 = $address5;
        $this->city = $city;
        $this->postcode = $postcode;
        $this->state = $state;
        $this->country = $country;
        $this->phoneNumber = $phoneNumber;
        $this->email = $email;
    }

    /**
     * Creates the address for the customer (if the customer exists and the address doesn't exist) and return the array to be stored in JSON
     *
     * @param Account $account
     *
     * @param null|Customer $customer
     *
     * @return array
     */
    public function createAndFormatAddress(Account $account, $customer = null)
    {
        //TODO: Handle creation of address for customer

        return [
            'company' => $this->company,
            'name' => $this->name,
            'address1' => $this->address1,
            'address2' => $this->address2,
            'address3' => $this->address3,
            'address4' => $this->address4,
            'address5' => $this->address5,
            'city' => $this->city,
            'postcode' => $this->postcode,
            'state' => $this->state,
            'country' => $this->country,
            'phone_number' => is_array($this->phoneNumber) ? implode(', ', $this->phoneNumber) : $this->phoneNumber,
            'email' => $this->email,
        ];
    }

}
