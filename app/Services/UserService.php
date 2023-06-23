<?php

namespace App\Services;

use App\Models\User;
use App\Models\whatsapp\Utils;

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

    public static function getAdmin($phone = false, $role = Utils::USER_ROLE_ADMIN) {
        
        return  $phone ? User::where(['role' => $role, 'phone' => $phone])->first() : 
                User::where('role', $role)->first();
    }
}
