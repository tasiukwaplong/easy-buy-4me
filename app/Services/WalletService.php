<?php

namespace App\Services;

use App\Models\User;
use App\Models\Wallet;
use App\utils\monnify\MonnifyConfig;
use App\utils\monnify\MonnifyHelper;
use Illuminate\Support\Facades\Http;
use Nette\Utils\Random;

class WalletService
{
    /**
     * Function creates new wallet for user
     *
     * @param User $user
     * @return void
     */
    public function createWallet(User $user)
    {

        //Get new monnify virtual account
        $responseBody = $this->createMonnifyAccount($user)['responseBody'];

        //Get the account created
        $account = $responseBody['accounts'][0];

        //Create a new Wallat record for this uder
        Wallet::create([
            'bank' => $account['bankName'],
            'account_name' => $account['accountName'],
            'account_number' => $account['accountNumber'],
            'account_reference' => $responseBody['accountReference'],
            'bank_code' => $account['bankCode'],
            'user_id' => $user->id
        ]);
    }

    /**
     * function to add or withdraw fund to/from wallet
     *
     * @param [type] $amount
     * @param Wallet $wallet
     * @param bool $topUp determins whether it is a top up or withdrawal operation
     * @return void
     */

    public function alterBalance($amount, Wallet $wallet, bool $topUp)
    {

        if ($topUp) {
            
            $currentBalance = $wallet->balance + $amount;
            $wallet->balance = doubleval($currentBalance);

        } else {
            try {
                if ($wallet->balance < $amount) {
                    throw new \Exception("Insufficient balance " . $wallet->balance);
                }

                $currentBalance = $wallet->balance - $amount;
                $wallet->balance = doubleval($currentBalance);

            } catch (\Throwable $th) {
                throw new \Exception($th->getMessage());
            }
        }
        $wallet->save();
    }

    /**
     * Function to delete a wallet
     *
     * @param Wallet $wallet
     * @return void
     */
    public function deleteWallet(Wallet $wallet)
    {

        $accountReference = $wallet->account_reference;

        //Get Access token
        $accessToken = MonnifyHelper::getAccessToken();
        $requestUrl = env('MONNIFY_BASE_URL') . MonnifyConfig::DELETE_VIRTUAL_ACCOUNT . $accountReference;

        try {

            //Deallocate account in monnify
            $accountDeallocationRequestResponse = Http::withToken($accessToken)->delete($requestUrl);

            if (!($accountDeallocationRequestResponse->successful() and $accountDeallocationRequestResponse->json()['requestSuccessful'])) {
                throw new \Exception($accountDeallocationRequestResponse->json()['responseMessage']);
            }

            //Delete Wallet from database
            Wallet::destroy($wallet->id);
        } catch (\Throwable $th) {
            return $th->getMessage();
        }
    }


    /**
     * Function to create new monnify virtual account
     *
     * @param User $user
     * @return void
     */
    private function createMonnifyAccount(User $user)
    {
        //Get bearer authentication token
        $token = MonnifyHelper::getAccessToken();

        $requestUrl = env('MONNIFY_BASE_URL') . MonnifyConfig::CREATE_VIRTUAL_ACCOUNT;

        $newMonnifyAccountRequestBody = [
            "accountReference" => str_replace('.', '', str_replace("@", '', $user->email)) . Random::generate(6),
            "accountName" => "$user->first_name $user->last_name",
            "currencyCode" => MonnifyConfig::NGN_CURRENCY_CODE,
            "contractCode" => env('MONNIFY_CURRENCY_CODE'),
            "customerEmail" => $user->email,
            "customerName" => "$user->first_name $user->last_name",
            "preferredBanks" => [MonnifyConfig::WEMA_BANK],
            "getAllAvailableBanks" => false
        ];

        try {
            //Send new account request to monnify
            $createNewMonnifyAccountResponse = Http::withToken($token)->post($requestUrl, $newMonnifyAccountRequestBody);

            //Check if request is unsuccessful
            if (!$createNewMonnifyAccountResponse->successful()) {
                throw new \Exception($createNewMonnifyAccountResponse['responseMessage']);
            }
            return $createNewMonnifyAccountResponse->json();
        } catch (\Throwable $th) {
            dd($th->getMessage());
        }
    }
}
