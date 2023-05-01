<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\Wallet;
use App\Services\WalletService;
use App\utils\monnify\MonnifyHelper;
use Illuminate\Http\Request;

class MonnifyController extends Controller
{

    public function webhook(Request $request)
    {
        $requestBody = $request->all();

        $requestHash = $request->header('monnify-signature');
        $stringfyRequestBody = "'" . json_encode($requestBody) . "'";

        //Validate this request payload
        $validRequest = MonnifyHelper::validateWebHookRequest($stringfyRequestBody, env('MONNIFY_SECRETE'), $requestHash);

        if (
            $validRequest and
            $requestBody['eventType'] === "SUCCESSFUL_TRANSACTION" and
            $requestBody['eventData']['paymentMethod'] === "ACCOUNT_TRANSFER"
        ) {

            $responseBodyData = $requestBody['eventData'];

            $destinationWallet = Wallet::where('account_number', $responseBodyData['destinationAccountInformation']['accountNumber'])->first();

            if ($destinationWallet) {

                $walletService = new WalletService();
                $walletService->alterBalance($responseBodyData['settlementAmount'], $destinationWallet, true);

                //Create new transaction
                Transaction::create([
                    'transaction_reference' => $responseBodyData['transactionReference'],
                    'amount' => $responseBodyData['amountPaid'],
                    'date' => $responseBodyData['paidOn'],
                    'method' => $responseBodyData['paymentMethod'],
                    'description' => $responseBodyData['paymentDescription'],
                    'payment_reference' => $responseBodyData['paymentReference'],
                    'status' => $responseBodyData['paymentStatus'],
                    'user_id' => $destinationWallet->user->id
                ]);
            }

            return response([], 200);
        }
    }
}
