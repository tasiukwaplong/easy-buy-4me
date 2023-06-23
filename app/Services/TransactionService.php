<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderInvoice;
use App\Models\Transaction;
use App\Models\User;
use App\Models\whatsapp\Utils;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TransactionService {

    public function addTransaction(array $detials, $type) {

        $transaction = Transaction::create($detials);
        $order = $transaction->order;

        OrderInvoice::create([
            'customer_name' => $order->user->first_name . " " . $order->user->last_name,
            'type' => $type,
            'status' => $transaction->status,
            'transaction_id' => $transaction->id,
            'invoice_no' => Str::replace("trans-", "", $transaction->transaction_reference)
        ]);

        return $transaction;
    }

    public function updateTransaction(Transaction $transaction, array $updates) {

        $transaction->update($updates);
        $orderInvoice = $transaction->orderInvoice;

        $orderInvoice->status = $transaction->status;
        $orderInvoice->save();
        
        return $transaction;
    }

    public static function fetchUserTransaction(User $user, int $nextPage = 0) : array {

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