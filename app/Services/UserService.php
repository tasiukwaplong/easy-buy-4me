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

    public function updateUserParam($field, $newValue, $userPhone) : bool
    {
        $user = $this->getUserByPhoneNumber($userPhone);

        if ($user) {
            $user->$field = $newValue;
            
            return $user->save();
        }

        return false;
    }
}
