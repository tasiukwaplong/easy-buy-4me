<?php

namespace App\Services;

use App\Events\WalletLowEvent;
use App\Exceptions\InsufficientFundException;
use App\Models\DataPlan;
use App\Models\Order;
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

    /**
     * Function to fetch a particular data plan
     *
     * @param array $attributes
     * @return string|DataPlan
     */
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

                $destinationPhone = Str::replace("+234", 0, $destinationPhone);

                //send data here, may throw exception
                $response = $this->sendDataRequest($dataPlan->network_code, $destinationPhone, $dataPlan->dataplan, $transactionReference);

                if (Str::startsWith($response['success'], 'false') and (strcasecmp($response['message'], "Insufficient Balance") === 0)) {

                    //Notify admin that wallet is low
                    event(new WalletLowEvent(Utils::ADMIN_WALLET_EASY_ACCESS));
                    throw new Exception("Error Processing Request");

                }

                //Debit user
                $walletService->alterBalance($dataPlan->price, $userWallet, false);

                $transactionReference = ($response['reference_no']) ?? $transactionReference;

                $this->status = Utils::TRANSACTION_STATUS_SUCCESS;

            } 
            else throw new InsufficientFundException("Insufficient balance.\nWallet balance: $userWallet->balance");
        } 
        catch (InsufficientFundException $exception) {
            $this->status = Utils::TRANSACTION_STATUS_INSUFFICIENT_BALANCE;
        } 
        catch (\Throwable $th) {
            $this->status = Utils::TRANSACTION_STATUS_UNSUCCESSFUL;
        }
        finally {

            $order = Order::create([
                'order_id' => strtoupper(Random::generate(35)),
                'description' => $dataPlan->description,
                'total_amount' => $dataPlan->price,
                'status' => $this->status,
                'user_id' => $this->user->id
            ]);

             //create transaction
             $transactionService = new TransactionService();
             $transactionService->addTransaction([
                 'transaction_reference' => $transactionReference,
                 'amount' => $dataPlan->price,
                 'date' => now(),
                 'order_id' => $order->id,
                 'method' => Utils::PAYMENT_METHOD_WALLET,
                 'description' => $dataPlan->description,
                 'status' => $this->status,
                 'user_id' => $this->user->id
             ],
                 Utils::ORDER_CATEGORY_DATA
             );

        }

        return $transactionReference;

       
    }

    public function getStatus()
    {
        return $this->status;
    }

    public static function getAllNetworks()
    {
        return DataPlan::distinct('network_name')->get('network_name');
    }

    public static function fetchDataPlans()
    {
        $dataPlans = DataPlan::orderBy('network_name')->get();;
        $networkNames = self::getAllNetworks();

        $groupedDataPlans = $networkNames->map(function ($networkName) use ($dataPlans) {
            $networkDataPlans = $dataPlans->filter(function ($dataPlan) use ($networkName) {
                return $dataPlan->network_name == $networkName->network_name;
            });
            return array($networkName->network_name => $networkDataPlans);
        }); 

        return $groupedDataPlans;
    }

    private function sendDataRequest($networkCode, $phoneNumber, $dataPlan, $transactionReference)
    {
        $requestBody = [
            'network' => $networkCode,
            'mobileno' => $phoneNumber,
            'dataplan' => $dataPlan,
            'client_reference' => $transactionReference
        ];

        $response = Http::withHeaders(["AuthorizationToken" => env('EASY_ACCESS_TOKEN'), "cache-control" => "no-cache"])
                    ->asForm()            
                    ->post("https://easyaccess.com.ng/api/data.php", $requestBody);

        return $response->json();
    }
}
