<?php

namespace App\Services;

use App\Models\User;

class UserService
{
    public function createUser(array $userInfo): User|string
    {

        try {
            $newUser = User::create($userInfo);
            return $newUser;
        } catch (\Throwable $th) {
            return "email or phone number already exists";
        }
    }

    public function getUserByPhoneNumber($phone): User|null
    {
        $user = User::where("phone", $phone)->first();
        return $user;
    }

    public function updateUserParam($values, $userPhone) : bool
    {
        $user = $this->getUserByPhoneNumber($userPhone);

        if ($user) {

            foreach($values as $field => $value) {
                $user->$field = $value;
            }
            
            return $user->save();
        }

        return false;
    }

    public function isRegisteredCustomer($customerPhoneNumber)
    {
        $user = $this->getUserByPhoneNumber($customerPhoneNumber);

        return ($user and $user->email and $user->first_name) ? $user : false;
    }

    public function getAdmin($phone = false) {
        
        return  $phone ? User::where(['is_admin', true, 'phone' => $phone])->first() : 
                User::where('is_admin', true)->first();
    }
}
