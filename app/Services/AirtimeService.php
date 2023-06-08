<?php

namespace App\Services;

use App\Models\User;
use App\Models\whatsapp\Utils;
use Flutterwave\Payload;
use Flutterwave\Service\Bill;
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

        //Initialize a new wallet service
        $walletService = new WalletService();

        //Get user wallet
        $userWallet = $walletService->getWallet($this->user);

        $billService = new Bill();

        $payload = new Payload();

        $payload->set("country", "NG");
        $payload->set("customer", $destinationPhone);
        $payload->set("amount", $amount);
        $payload->set("type", "AIRTIME");
        $payload->set("reference", Random::generate(64));

        try {

            $response = $billService->createPayment($payload);

            $this->status = Utils::TRANSACTION_STATUS_SUCCESS;
            $walletService->alterBalance($amount, $userWallet, false);

        } catch (\Throwable $th) {

            dd($th->getMessage());

            $this->status = (Str::startsWith($th->getMessage(), 'Insufficient balance ')) ?
                Utils::TRANSACTION_STATUS_INSUFFICIENT_BALANCE :
                Utils::TRANSACTION_STATUS_UNSUCCESSFUL;
        }

         //Create new Transaction for this user
         $transactionService = new TransactionService();

         $transactionService->addTransaction([
             'transaction_reference' => "trans-".Random::generate(64),
             'amount' => $amount,
             'date' => now(),
             'method' => "WALLET",
             'description' => "N$amount airtime purchase for $destinationPhone",
             'payment_reference' => "pay-".Random::generate(64),
             'status' => $this->status,
             'user_id' => $this->user->id
         ]);

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
            
            case Utils::TRANSACTION_STATUS_PENDING:
                $response .= "Transaction pending";

            default:
                $response .= "An unknown error occured";
                break;
        }

        return $response;
    }
}
