<?php

namespace App\Services;

use App\Events\WalletLowEvent;
use App\Exceptions\InsufficientFundException;
use App\Models\DataPlan;
use App\Models\User;
use App\Models\whatsapp\Utils;
use Exception;
use Illuminate\Support\Facades\Http;
use Nette\Utils\Random;
use Illuminate\Support\Str;

class DataService
{

    private int $status;
    private User $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public static function get(array $attributes)
    {
        $thisDataPlan = DataPlan::where($attributes)->first();
        return ($thisDataPlan) ? $thisDataPlan : Utils::DATA_STATUS_NOT_FOUND;
    }

    public function confirmDataPurchase(DataPlan $dataPlan, $destinationPhone)
    {

        $transactionReference = "trans-" . Random::generate(64);

        $walletService = new WalletService();
        $userWallet = $walletService->getWallet($this->user);

        try {

            if ($walletService->isFundsAvailable($this->user, $dataPlan->price)) {

                //send data here, may throw exception
                $response = $this->sendDataRequest($dataPlan->network_code, $destinationPhone, $dataPlan->dataplan, $transactionReference);
                if(Str::startsWith($response['success'], 'false') and $response['message'] == "Insufficient Balance") {

                    //Notify admin that wallet is low
                    event(new WalletLowEvent(Utils::ADMIN_WALLET_EASY_ACCESS));
                    throw new Exception("Error Processing Request");
                    

                }

                //Debit user
                $walletService->alterBalance($dataPlan->price, $userWallet, false);

                $this->status = Utils::TRANSACTION_STATUS_SUCCESS;

            } else throw new InsufficientFundException("Insufficient balance.\nWallet balance: $userWallet->balance");
        } 
        catch (InsufficientFundException $exception) {
            $this->status = Utils::TRANSACTION_STATUS_INSUFFICIENT_BALANCE;
        } 
        catch (\Throwable $th) {
            $this->status = Utils::TRANSACTION_STATUS_UNSUCCESSFUL;
        }

        //create transaction
        $transactionService = new TransactionService();

        $transactionService->addTransaction([
            'transaction_reference' => $transactionReference,
            'amount' => $dataPlan->price,
            'date' => now(),
            'method' => "WALLET",
            'description' => $dataPlan->description,
            'payment_reference' => "pay-" . Random::generate(64),
            'status' => $this->status,
            'user_id' => $this->user->id
        ]);
    }

    public function getStatus()
    {
        return $this->status;
    }

    public static function getAllNetworks()
    {

        return DataPlan::distinct('network_name')->get('network_name')->all();
    }

    public static function fetchDataPlans(string $networkName)
    {
        return DataPlan::where("network_name", $networkName)->get();
    }

    private function sendDataRequest($networkCode, $phoneNumber, $dataPlan, $transactionReference)
    {
        $requestBody = [
            'network' => $networkCode,
            'mobileno' => "07035002025",
            'dataplan' => $dataPlan,
            'client_reference' => "transactionReference"
        ];

        $response = Http::withHeaders(["AuthorizationToken" => env('EASY_ACCESS_TOKEN'), "cache-control" => "no-cache"])
                    ->asForm()            
                    ->post("https://easyaccess.com.ng/api/data.php", $requestBody);

        return $response->json();
    }
}
