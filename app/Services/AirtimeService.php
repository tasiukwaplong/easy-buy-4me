<?php

namespace App\Services;

use App\Exceptions\InsufficientFundException;
use App\Models\Order;
use App\Models\User;
use App\Models\whatsapp\Utils;
use Illuminate\Support\Facades\Http;
use Nette\Utils\Random;
use Illuminate\Support\Str;

class AirtimeService
{

    private User $user;
    private int $status;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * Function to purchase airtime for 
     * customer
     *
     * @param [type] $destinationPhone
     * @param [type] $amount
     * @return void
     */
    public function buyAirtime($destinationPhone, $amount)
    {
        $amount = doubleval($amount);
        $reference = '';

        //Initialize a new wallet service
        $walletService = new WalletService();

        //Get user wallet
        $userWallet = $walletService->getWallet($this->user);

        $airtimeRequestBody = [
            'network' => $this->getPhoneNetwork($destinationPhone),
            'amount' => intval($amount),
            'mobileno' => $destinationPhone,
            'airtime_type' => '001'
        ];

        try {

            $response = Http::withHeaders(["AuthorizationToken" => env('EASY_ACCESS_TOKEN'), "cache-control" => "no-cache"])
                        ->asForm()
                        ->post("https://easyaccess.com.ng/api/airtime.php", $airtimeRequestBody)->json();

            $reference = $response['reference_no'] ?? Random::generate(30);

            if(($response['success'] == "true") and ($response['status'] == 'Successful')) {

                $walletService->alterBalance($amount, $userWallet, false);
                $this->status = Utils::TRANSACTION_STATUS_SUCCESS;

            }

            elseif(Str::startsWith($response['message'],'Amount Too Low')) {
                $this->status = Utils::AIRTIME_INVALID_AMOUNT;
            }

            else {
                throw new \Exception($response['message']);
            }

        } 
        catch(InsufficientFundException $e) {
            $this->status = Utils::TRANSACTION_STATUS_INSUFFICIENT_BALANCE;
        }
        
        catch (\Throwable $th) {

            $this->status = (strcasecmp($th->getMessage(), 'Insufficient Balance') === 0) ?
                Utils::TRANSACTION_STATUS_INSUFFICIENT_BALANCE :
                Utils::TRANSACTION_STATUS_UNSUCCESSFUL;

        } 
        finally{

            $order = Order::create([
                'order_id' => strtoupper(Random::generate(35)),
                'description' => Utils::ORDER_CATEGORY_AIRTIME . "(N$amount - $destinationPhone)",
                'total_amount' => $amount,
                'status' => Utils::ORDER_STATUS_DELIVERED,
                'user_id' => $this->user->id
            ]);
    
            //Create new Transaction for this user
            $transactionService = new TransactionService();
    
            $transactionService->addTransaction([
                'transaction_reference' => $reference,
                'amount' => $amount,
                'date' => now(),
                'order_id' => $order->id,
                'method' => Utils::PAYMENT_METHOD_WALLET,
                'description' => "N$amount airtime purchase for $destinationPhone",
                'status' => $this->status,
                'user_id' => $this->user->id
            ], Utils::ORDER_CATEGORY_AIRTIME);

        }

    }

    /**
     * Function to get status of data purchase
     *
     * @return string
     */
    public function getStatus(): string
    {
        $response = "";
        $walletBalance = $this->user->wallet->balance;

        switch ($this->status) {

            case Utils::TRANSACTION_STATUS_SUCCESS:
                $response .= "Airtime Purchase successful";
                break;

            case Utils::TRANSACTION_STATUS_UNSUCCESSFUL:
                $response .= "Sorry, could not complete your airtime purchase. Try again later";
                break;

            case Utils::TRANSACTION_STATUS_INSUFFICIENT_BALANCE:
                $response .= "Insufficient funds\nWallet Balance: $walletBalance";
                break;
            case Utils::AIRTIME_INVALID_AMOUNT:
                $response .= "Amount Too Low, Minimum Amount is N50";
                break;
            
            case Utils::TRANSACTION_STATUS_PENDING:
                $response .= "Transaction pending";

            default:
                $response .= "An unknown error occured";
                break;
        }

        return $response;
    }

    private function getPhoneNetwork($phone) {

        $start = substr($phone, 0, 4);
        $networkName = "";

        foreach (Utils::NETWORK_CODES as $network => $codes) {
            if(in_array($start, $codes)) 
                $networkName = $network;   
        }

        return DataService::get(array('network_name' => $networkName))->network_code;
        
    }
}
