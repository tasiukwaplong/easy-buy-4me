<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\User;
use App\Models\whatsapp\Utils;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Arr;

class TransactionService {

    public function addTransaction(array $detials) {
        return Transaction::create($detials);
    }

    public function fetchUserTransaction(User $user, int $nextPage = 0) : array {

        $transactions = "";

        $paginator = new Paginator($user->transactions, 10, $nextPage);

        foreach ($paginator as $transaction) {
            
            $status = Utils::TRANSACTION_STATUS[$transaction->status];

            $transactions .= "$transaction->description\nstatus:*$status*\ndate:$transaction->created_at\n\n";
        }        

        return array("transactions" => $transactions, 
                    "nextPage" => $nextPage + 1, 
                    'lastPage' => $paginator->onLastPage());

    }
}