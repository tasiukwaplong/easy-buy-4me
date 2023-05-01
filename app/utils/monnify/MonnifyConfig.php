<?php

namespace App\utils\monnify;

class MonnifyConfig {

    /**
     * Monnify URLs
     */
    public const LOGIN_URL = "/api/v1/auth/login";
    public const CREATE_VIRTUAL_ACCOUNT = "/api/v2/bank-transfer/reserved-accounts";
    public const DELETE_VIRTUAL_ACCOUNT = "/api/v1/bank-transfer/reserved-accounts/reference/";
    public const PAYMENT_INIT = "/api/v1/merchant/transactions/init-transaction";
    public const TRANSACTION_STATUS = "/api/v2/transactions/";
    public const GET_BANKS_URL = "/api/v1/sdk/transactions/banks";
    public const VALIDATE_ACCOUNT = "/api/v1/disbursements/account/validate?";

    /**
     * Monnify constants
     */
    public const BASIC_AUTHORIZATION_PREFIX = "Basic ";
    public const BEARER_AUTHORIZATION_PREFIX = "Bearer ";
    public const NGN_CURRENCY_CODE = "NGN";

    /**
     * Bank Codes
     */
    public const WEMA_BANK = "035";

}