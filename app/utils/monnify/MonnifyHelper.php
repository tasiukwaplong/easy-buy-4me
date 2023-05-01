<?php

namespace App\utils\monnify;

use Illuminate\Support\Facades\Http;

class MonnifyHelper
{

    public static function base64Secrete(): string
    {
        return MonnifyConfig::BASIC_AUTHORIZATION_PREFIX . base64_encode(env('MONNIFY_KEY') . ":" . env('MONNIFY_SECRETE'));
    }

    public static function getAccessToken(): string
    {

        $response = Http::withHeaders(["Authorization" => self::base64Secrete()])
            ->post(env("MONNIFY_BASE_URL") . MonnifyConfig::LOGIN_URL);

        return $response->json()['responseBody']['accessToken'];
    }

    public static function validateWebHookRequest($stringifiedData, $clientSecret, $requestHash)
    {
        $computedHash = hash_hmac('sha512', $stringifiedData, $clientSecret);
        
        return $computedHash === $requestHash;
    }
}
