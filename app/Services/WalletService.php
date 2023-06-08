<?php

namespace App\Services;

use App\Exceptions\InsufficientFundException;
use App\Models\MonnifyAccount;
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
     */
    public function createWallet(User $user)
    {
        //Get new monnify virtual account
        $responseBody = $this->createMonnifyAccount($user);

        if (is_string($responseBody)) {
            return $responseBody;
        }

        $responseBody = $responseBody['responseBody'];

        //Get the account created
        $accounts = $responseBody['accounts'];

        foreach ($accounts as $account) {
            MonnifyAccount::create([
                'bank' => $account['bankName'],
                'account_name' => $account['accountName'],
                'account_number' => $account['accountNumber'],
                'account_reference' => Random::generate(64),
                'bank_code' => $account['bankCode'],
                'user_id' => $user->id
            ]);
        }

        //Create a new Wallat record for this uder
        Wallet::create([

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
        } 
        else {
            if ($wallet->balance < $amount) {
                throw new InsufficientFundException("Insufficient balance " . $wallet->balance);
            }

            $currentBalance = $wallet->balance - $amount;
            $wallet->balance = doubleval($currentBalance);
        }

        $wallet->save();
    }

    /**
     * Function to delete a wallet
     *
     * @param Wallet $wallet
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

    public function checkBalance(User $user)
    {
        return $user->wallet->balance;
    }

    public function isFundsAvailable(User $user, $amount)
    {
        return $user->wallet->balance >= $amount;
    }

    public function getWallet(User $user)
    {
        return $user->wallet;
    }


    /**
     * Function to create new monnify virtual account
     *
     * @param User $user
     */
    private function createMonnifyAccount(User $user)
    {
        //Get bearer authentication token
        $token = MonnifyHelper::getAccessToken();

        $requestUrl = env('MONNIFY_BASE_URL') . MonnifyConfig::CREATE_VIRTUAL_ACCOUNT;

        $newMonnifyAccountRequestBody = [
            "accountReference" => str_replace('.', '', str_replace("@", '', $user->temp_email)) . Random::generate(10),
            "accountName" => "$user->first_name $user->last_name",
            "currencyCode" => MonnifyConfig::NGN_CURRENCY_CODE,
            "contractCode" => env('MONNIFY_CURRENCY_CODE'),
            "customerEmail" => $user->temp_email,
            "customerName" => "$user->first_name $user->last_name",
            "getAllAvailableBanks" => true
        ];

        try {
            //Send new account request to monnify
            $createNewMonnifyAccountResponse = Http::withToken($token)
                ->retry(3)
                ->post($requestUrl, $newMonnifyAccountRequestBody);

            //Check if request is unsuccessful
            if (!$createNewMonnifyAccountResponse->successful()) {
                throw new \Exception($createNewMonnifyAccountResponse['responseMessage']);
            }

            return $createNewMonnifyAccountResponse->json();
        } catch (\Throwable $th) {
            return "Could not Create Wallet, something went wrong";
        }
    }
}
