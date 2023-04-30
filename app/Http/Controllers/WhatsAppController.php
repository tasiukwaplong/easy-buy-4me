<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\WalletService;
use Illuminate\Http\Request;

class WhatsAppController extends Controller
{
    //

    public function test() {
        $walletService = new WalletService();

        $user = User::find(1);

        $walletService->createWallet($user);
    }
}
