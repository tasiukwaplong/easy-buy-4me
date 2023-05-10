<?php

namespace App\Services;

use App\Models\Customer;

class CustomerService
{
    public function createCustomer(array $userInfo) : Customer {
        
        $newUser = Customer::create($userInfo);
        return $newUser;
    }

}
