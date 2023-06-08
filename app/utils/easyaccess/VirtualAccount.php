<?php

namespace App\utils\easyaccess;

class VirtualAccount {

    public string $bank;
    public string $account;
    public string $accountName;


    public function __construct(string $bank, string $account, string $accountName)
    {
        $this->bank = $bank;
        $this->account = $account;
        $this->accountName = $accountName;
    }

    
}