<?php

namespace App\Services;

use App\Events\DispatcherOrderRecievedAdminEvent;
use App\Events\DispatcherOrderRecievedUserEvent;
use App\Events\OrderAssignedToDispatcherEvent;
use App\Events\OrderPlacedEvent;
use App\Mail\EmailVerification;
use App\Models\EasyLunch;
use App\Models\EasyLunchSubscribers;
use App\Models\Errand;
use App\Models\Item;
use App\Models\Order;
use App\Models\User;
use App\Models\Vendor;
use App\Models\whatsapp\ResponseMessages;
use App\Models\whatsapp\Utils;
use App\utils\easyaccess\VirtualAccount;
use Illuminate\Support\Facades\Http;
use Nette\Utils\Random;
use Illuminate\Support\Str;

class ResponseService
{
    private string $origin;
    private $data;
    private $responseData;

    public function __construct(string $origin, $data)
    {
        $this->data = $data;
        $this->origin = $origin;
    }

    public function processRequest()
    {

        if ($this->origin === Utils::ORIGIN_WHATSAPP or $this->origin === Utils::ORIGIN_VERIFICATION or $this->origin === Utils::ADMIN_EVENTS) {

            $this->processWhatsappRequest();

        } elseif ($this->origin === Utils::ORIGIN_FACEBOOK) {
            //process requests from facebook
        } 
        
        elseif ($this->origin === Utils::ORIGIN_TELEGRAM) {
            //process requests from Telegram
        } 
        
        elseif ($this->origin === Utils::ORIGIN_TWITTER) {
            //process requests from Twitter
        } 
        
        else {
            //send error response
        }
    }

    /**
     * Function to process all whatsapp related requests
     *
     * @return void
     */
    private function processWhatsappRequest()
    {
        $userService = new UserService();
        $orderService = new OrderService();

        if ($this->origin === Utils::ORIGIN_WHATSAPP) {

            $incomingMessage = $this->data['entry'][0]['changes'][0]['value']['messages'][0];
            $incomingMessageType = $incomingMessage['type'];
            $customerPhoneNumber = $incomingMessage['from'];

            //Get this user by phone number
            $user = $userService->getUserByPhoneNumber($customerPhoneNumber);

            if ($userService->isRegisteredCustomer($customerPhoneNumber)) {

                if ($incomingMessageType === Utils::TEXT) {

                    $text = strtolower($incomingMessage['text']['body']);

                    if (strcasecmp($text, Utils::USER_INPUT_ORDER_STATUS) === 0) {

                        $orderService = new OrderService();
                        $orders = $orderService->getUserPendingOrders($user, [Utils::ORDER_STATUS_INITIATED, Utils::ORDER_STATUS_PROCESSING, Utils::ORDER_STATUS_ENROUTE]);

                        $this->responseData = ResponseMessages::showOrderStatus($orders, $user);
                    } 
                    
                    elseif (strcasecmp($text, Utils::USER_INPUT_VENDORS) === 0) {

                        $this->responseData = ResponseMessages::allVendors($customerPhoneNumber);
                    } 
                    
                    elseif (strcasecmp($text, Utils::USER_INPUT_CONTACT_ADMIN) === 0) {

                        $this->responseData = ResponseMessages::showContactAdmin($customerPhoneNumber);
                    } 
                    
                    elseif (strcasecmp($text, Utils::USER_INPUT_CART) === 0) {

                        $userPendingOrder = $orderService->getUserPendingOrder($user);

                        $this->responseData = $userPendingOrder ? ResponseMessages::sendUserCart($customerPhoneNumber, $userPendingOrder, false) :
                            ResponseMessages::sendUserCart($customerPhoneNumber, false, true);
                    } 
                    
                    elseif (strcasecmp($text, Utils::USER_INPUT_DATA) === 0) {
                        $networkNames = DataService::getAllNetworks();
                        $this->responseData = ResponseMessages::showDataNetworks($customerPhoneNumber, $networkNames);
                    } 
                    
                    elseif (strcasecmp($text, Utils::USER_INPUT_ERRAND) === 0 or strcasecmp($text, "Errands") === 0) {
                        $this->responseData = ResponseMessages::errandHome($customerPhoneNumber);
                    } 
                    
                    elseif (strcasecmp($text, Utils::USER_INPUT_EASY_LUNCH) === 0) {

                        $this->responseData = $this->getEasylunchHome($user);
                    } 
                    
                    elseif (strcasecmp($text, Utils::USER_INPUT_WALLET) === 0) {
                        $this->responseData = ResponseMessages::userWallets($user);
                    } 
                    
                    elseif (strcasecmp($text, Utils::USER_INPUT_AIRTIME) === 0) {
                        $this->responseData = ResponseMessages::showAirtime($customerPhoneNumber);
                    } 
                    
                    elseif (strcasecmp($text, Utils::USER_INPUT_TRANSACTIONS) === 0) {
                        $this->responseData = $this->getUserTransactions($customerPhoneNumber, $user);
                    } 
                    
                    elseif (Str::startsWith($text, "purchase")) {

                        $text = strtoupper($text);

                        $parts = explode(" ", Str::replace("PURCHASE ", "", $text));

                        $networkName = $parts[0];
                        $dataPlan = $parts[1];
                        $type = $parts[2];
                        $destinationPhone = ($parts[3] ?? false) ? "+234" . Str::substr($parts[3], 1) : "+$customerPhoneNumber";

                        $thisData = DataService::get(array('name' => "$dataPlan $type", 'network_name' => $networkName));

                        $this->responseData = (is_int($thisData) and $thisData == Utils::DATA_STATUS_NOT_FOUND) ?
                            ResponseMessages::wrongDataPlanEntry($customerPhoneNumber, $text) :
                            ResponseMessages::confirmDataPurchase($customerPhoneNumber, $thisData, $destinationPhone);
                    } 
                    
                    elseif (Str::startsWith($text, 'recharge ')) {

                        $parts = explode(" ", $text);

                        $destinationPhone = "+234" . substr($parts[1], 1);
                        $amount = $parts[2];

                        $airtimeService = new AirtimeService($user);
                        $airtimeService->buyAirtime($destinationPhone, $amount);
                        $transactionStatusMessage = $airtimeService->getStatus();

                        $this->responseData = ResponseMessages::dataPurchaseStatus($customerPhoneNumber, $transactionStatusMessage);
                    } 

                    elseif(Str::startsWith($text, "delivery address ")) {

                        $parts = explode(" ", $text);
                        $easylunchRequest = strcasecmp("EASYLUNCH", $parts[2]) === 0;

                        $address = strtoupper($easylunchRequest ? Str::replace("delivery address easylunch ", "", $text) : Str::replace("delivery address ", "", $text));

                        $order = $orderService->getUserPendingOrder($user);

                        if(!$order) {
                            $this->responseData = ResponseMessages::noPendingOrder($customerPhoneNumber);
                        } else 
                        
                        {

                            $orderErrand = Errand::where('order_id', $order->id)->first();

                            if ($orderErrand) {

                                $orderErrand->delivery_address;
                                $orderErrand->save();
                            } else {
                                $orderErrand = Errand::create([
                                    'destination_phone' => $customerPhoneNumber,
                                    'dispatcher' => "",
                                    'delivery_address' => $address,
                                    'delivery_fee' => 0.0,
                                    'status' => Utils::ORDER_STATUS_INITIATED,
                                    'order_id' => $order->id
                                ]);
                            }

                            $this->responseData = ResponseMessages::confirmDeliveryAddress($customerPhoneNumber, $order, $address, $easylunchRequest);
                        }
                    }
                    
                    elseif (str_starts_with($text, "process-order-")) {

                        //this operations are handled by admin only
                        if (in_array($user->role, [Utils::USER_ROLE_ADMIN, Utils::USER_ROLE_SUPER_ADMIN], true)) {

                            $processMessage = str_replace("process-order-", "", $text);

                            $parts = explode(":", $processMessage);

                            $orderId = strtolower($parts[0]);
                            $dispatcherPhone = $parts[1];
                            $fee = $parts[2];

                            $order = $orderService->processOrder($orderId, $dispatcherPhone, $fee);

                            $this->responseData = ResponseMessages::adminOrderProcessedSuccess($customerPhoneNumber, $order);
                        }
                    } 
                    
                    elseif (in_array($text, Utils::USER_INPUTS_GREETINGS) or strcasecmp($text, Utils::USER_INPUT_MENU) === 0) {
                        $this->responseData = ResponseMessages::dashboardMessage($user);
                    } 
                    
                    elseif (strcasecmp($text, Utils::USER_INPUT_MORE) === 0) {
                        $this->responseData = ResponseMessages::showMore($customerPhoneNumber);
                    } 
                    
                    else {
                        //Send error message to existing customer
                        $this->responseData = ResponseMessages::errorMessage($customerPhoneNumber, true, false);
                    }
                }

                elseif ($incomingMessageType === Utils::INTERACTIVE) {

                    $interactiveMessage = $incomingMessage['interactive'];

                    if ($interactiveMessage['type'] == Utils::LIST_REPLY) {

                        $interactiveMessageId = $interactiveMessage[Utils::LIST_REPLY]['id'];

                        switch ($interactiveMessageId) {

                            case Utils::BUTTONS_ADD_EASY_LUNCH_SUB: {
                                
                                $this->responseData = ResponseMessages::easyLunchPackages($user);
                                break;
                            }

                            case Utils::DATA: {
                                    $networkNames = DataService::getAllNetworks();
                                    $this->responseData = ResponseMessages::showDataNetworks($customerPhoneNumber, $networkNames);
                                    break;
                                }

                            case Utils::TRANSACTION_HISTORY: {

                                    $this->responseData = $this->getUserTransactions($customerPhoneNumber, $user);
                                    break;
                                }

                            case Utils::EASY_LUNCH: {
                                    $this->responseData = $this->getEasylunchHome($user);
                                    break;
                                }

                            case Utils::MORE: {
                                    $this->responseData = ResponseMessages::showMore($customerPhoneNumber);
                                    break;
                                }

                            case Utils::MY_WALLET: {
                                    $this->responseData = ResponseMessages::userWallets($user);
                                    break;
                                }

                            case Utils::MY_CART: {

                                    $userPendingOrder = $orderService->getUserPendingOrder($user);

                                    $this->responseData = $userPendingOrder ? ResponseMessages::sendUserCart($customerPhoneNumber, $userPendingOrder, false) :
                                        ResponseMessages::sendUserCart($customerPhoneNumber, false, true);
                                    break;
                                }

                            case Utils::ERRAND: {
                                    $this->responseData = ResponseMessages::errandHome($customerPhoneNumber);
                                    break;
                                }
                            case Utils::ERRAND_ORDER_FOOD: {
                                    $this->responseData = ResponseMessages::errandOrderFood($customerPhoneNumber, false);
                                    break;
                                }

                            case Utils::ERRAND_GROCERY_SHOPPING: {
                                    $this->responseData = ResponseMessages::errandGroceryShopping($customerPhoneNumber);
                                    break;
                                }

                            case Utils::ERRAND_ITEM_PICK_UP: {
                                    $this->responseData = ResponseMessages::errandItemPickUp($customerPhoneNumber);
                                    break;
                                }

                            case Utils::ERRAND_OTHER_ITEMS: {
                                    $this->responseData = ResponseMessages::errandOthers($customerPhoneNumber);
                                    break;
                                }

                            case Utils::ERRAND_VENDORS: {
                                    $this->responseData = ResponseMessages::allVendors($customerPhoneNumber);
                                    break;
                                }

                            case Utils::ERRAND_CUSTOM: {
                                    $this->responseData = ResponseMessages::errandCustom($customerPhoneNumber);
                                    break;
                                }

                            case Utils::AIRTIME: {
                                    $this->responseData = ResponseMessages::showAirtime($customerPhoneNumber);
                                    break;
                                }

                            default:
                                # code...
                                break;
                        }

                        $messge = $this->cleanMessage($interactiveMessageId);

                        if (str_starts_with($messge, "Order from ")) {

                            $parts = explode(":", str_replace("Order from ", "", $messge));

                            $vendor = Vendor::find($parts[0]);
                            $easylunchRequest = $parts[1] ?? false;

                            $this->responseData = ResponseMessages::vendorCatalog($customerPhoneNumber, $vendor, $easylunchRequest);
                        } 
                        
                        elseif (Str::startsWith($messge, 'easylunch-weekly:') or Str::startsWith($messge, 'easylunch-monthly:')) {

                            $easyLunchService = new EasyLunchService();
                            $easylunchId = Str::replace("easylunch-weekly:", "", $messge);

                            $easylunch = EasyLunch::find($easylunchId);

                            if($easyLunchService->isUsed($user, $easylunch)) {
                                $this->responseData = ResponseMessages::easylunchUsed($customerPhoneNumber, $easylunch);
                            }

                            elseif (count($easyLunchService->getSubscriptions($user)) > 0) {
                                
                                $easylunchOrder = $orderService->createEasylunchOrder($user, $easylunchId);

                                $this->responseData = ResponseMessages::confirmOrderCheckout($customerPhoneNumber, $easylunchOrder, true);
                            } 
                            
                            else $this->responseData = ResponseMessages::easyLunchHome($customerPhoneNumber);
                        }
                        
                        elseif (Str::startsWith($messge, "select-network:")) {

                            $networkName = Str::replace("select-network:", "", $messge);
                            $dataPlans = DataService::fetchDataPlans($networkName);

                            $this->responseData = ResponseMessages::showNetworkDataPlans($customerPhoneNumber, $dataPlans);
                        } 
                        
                        elseif (Str::startsWith($messge, "order-")) {

                            $order = $orderService->findUserCurrentOrder($user);

                            $parts = explode(":", str_replace("order-", "", $messge));

                            $itemId = $parts[1];
                            $vendorId = $parts[0];
                            $easylunchRequest = $parts[2] ?? false;

                            $updatedOrder = $orderService->addOrderedItem($order, $itemId, $vendorId, $easylunchRequest);

                            $this->responseData = ResponseMessages::currentOrder($customerPhoneNumber, $updatedOrder, $easylunchRequest);
                        } 
                        
                        elseif (str_starts_with($messge, "vendor-")) {

                            $vendorId = explode("-", $messge)[1];
                            $this->responseData = ResponseMessages::vendor($customerPhoneNumber, Vendor::find($vendorId));
                        } 
                        
                        elseif (str_starts_with($messge, "subscribe-easylunch-")) {

                            $parts = explode(":", str_replace("subscribe-easylunch-", '', $messge));

                            $type = $parts[0];
                            $easyLunchId = $parts[1];
                            $amount = $parts[2];

                            $easyLunchService = new EasyLunchService();
                            $easyLunchSub = $easyLunchService->subscribeUser($type, $user->id, $easyLunchId, $amount);

                            $this->responseData = ResponseMessages::easyLunchSubscribed($customerPhoneNumber, $easyLunchSub);
                        } 
                        
                        elseif (str_starts_with($messge, "select-wallet-")) {

                            $method = Utils::PAYMENT_METHOD_WALLET;

                            //Get user preferred payment methods
                            $payOnline = str_starts_with($messge, "select-wallet-online");
                            $payViaTransfer = str_starts_with($messge, "select-wallet-transfer");
                            $payOnDelivery = str_starts_with($messge, "select-wallet-delivery");

                            switch (true) {
                                case $payOnline:
                                    $method = Utils::PAYMENT_METHOD_ONLINE;
                                    break;

                                case $payOnDelivery:
                                    $method = Utils::PAYMENT_METHOD_ON_DELIVERY;
                                    break;
                                
                                case $payViaTransfer:
                                    $method = Utils::PAYMENT_METHOD_TRANSFER;
                                    break;
                                
                                default:
                                    # code...
                                    break;
                            }

                            $parts = explode(":", str_replace("select-wallet-", "", $messge));

                            $orderId = $parts[1];

                            $order = Order::find($orderId);
                            $walletService = new WalletService();

                            if ($order->status !== Utils::ORDER_STATUS_INITIATED) {
                                $this->responseData = ResponseMessages::orderAlreadyProcessed($customerPhoneNumber, $order);
                            } 
                            
                            else {

                                if ($method === Utils::PAYMENT_METHOD_WALLET) {

                                    if (Str::startsWith($order->description, "Easy lunch package")) {

                                        if ($walletService->isFundsAvailable($user, $order->total_amount)) {
                                            
                                            //Perform checkout
                                            $order = $orderService->performCheckout($orderId, $user->wallet->id, $method);

                                            //Notify user that their order has been placed
                                            $this->responseData = ResponseMessages::userOrderPlaced($customerPhoneNumber, $order);
                                        } 
                                        
                                        else {
                                            $this->responseData = ResponseMessages::insufficientBalnce($customerPhoneNumber, $orderId);
                                        }
                                    } 
                                    
                                    else {

                                        $this->responseData = ($walletService->isFundsAvailable($user, $order->total_amount)) ?
                                            ResponseMessages::enterOrderDeliveryAddress($customerPhoneNumber) :
                                            ResponseMessages::insufficientBalnce($customerPhoneNumber, $orderId);
                                    }
                                } 
                                
                                else {
                                    //Todo: Request delivery address
                                    $this->responseData = ResponseMessages::enterOrderDeliveryAddress($customerPhoneNumber);
                                }

                                //Update transaction payment method
                                $transactionService = new TransactionService();
                                $transactionService->updateTransaction($order->transaction, array('method' => $method));
                            }
                        }

                    } 
                    
                    elseif ($interactiveMessage['type'] === Utils::BUTTON_REPLY) {

                        switch ($interactiveMessage[Utils::BUTTON_REPLY]['id']) {

                            case Utils::BUTTONS_DATA_PLAN_NETWORKS: {

                                    $networkNames = DataService::getAllNetworks();
                                    $this->responseData = ResponseMessages::showDataNetworks($customerPhoneNumber, $networkNames);
                                    break;
                                }

                            case Utils::BUTTONS_FUND_MY_WALLET: {
                                    $this->responseData = ResponseMessages::showFundMyWallet($user);
                                    break;
                                }

                            case Utils::BUTTONS_GO_TO_DASHBOARD: {
                                    $this->responseData = ResponseMessages::dashboardMessage($user);
                                    break;
                                }

                            case Utils::BUTTONS_SUPPORT: {
                                    $admin = UserService::getAdmin();
                                    $this->responseData = ResponseMessages::showSupport($customerPhoneNumber, $admin);
                                    break;
                                }

                            case Utils::BUTTONS_CLEAR_CART: {

                                    $orderService->clearCart($user);

                                    $this->responseData = ResponseMessages::userCartCleared($customerPhoneNumber);

                                    break;
                                }

                            case Utils::BUTTONS_ORDER_ADD_ITEM: {

                                    $order = $orderService->getPreviousOrder($user);

                                    if ($order) {
                                        $orderItems = $order->orderedItems->sortBy(function ($item) {
                                            return $item->updated_at;
                                        });

                                        $vendor = Item::find($orderItems->last()->item_id)->vendor;

                                        $this->responseData = ResponseMessages::vendorCatalog($customerPhoneNumber, $vendor, false);
                                    } else  $this->responseData = ResponseMessages::allVendors($customerPhoneNumber);

                                    break;
                                }

                            case Utils::BUTTONS_ORDER_ADD_MORE_ITEM: {

                                    $this->responseData = ResponseMessages::allVendors($customerPhoneNumber);
                                    break;
                                }

                            default:
                                # code...
                                break;
                        }

                        $messge = $this->cleanMessage($interactiveMessage[Utils::BUTTON_REPLY]['id']);

                        if (Str::startsWith($messge, "buttons-transaction-history:")) {

                            $nextPage = Str::replace("buttons-transaction-history:", "", $messge, false);

                            $this->responseData = $this->getUserTransactions($customerPhoneNumber, $user, $nextPage);
                        } 

                        elseif(Str::startsWith($messge, 'buttons:order-delivered:')) {

                            $order = Order::find(Str::replace('buttons:order-delivered:', "", $messge));

                            $order->status = Utils::ORDER_STATUS_DELIVERED_DISPATCHER;
                            $order->save();

                            $this->responseData = ResponseMessages::thanksForService($customerPhoneNumber);

                        }

                        elseif(Str::startsWith($messge, "buttons-order-recieved:")) {

                            $order = Order::find(Str::replace('buttons-order-recieved:', "", $messge));

                            $order->status = Utils::ORDER_STATUS_DELIVERED;
                            $order->save();

                            $transactionService = new TransactionService();
                            $transactionService->updateTransaction($order->transaction, ['status' => Utils::ORDER_STATUS_DELIVERED]);

                            $this->responseData = ResponseMessages::thanksForPatronage($customerPhoneNumber, $order);
                        }

                        elseif(Str::startsWith($messge, "dispatcher-confirm:noted:")) {

                            if($user->role === Utils::USER_ROLE_DISPATCH_RIDER) {

                                $orderId = Str::replace("dispatcher-confirm:noted:", "", $messge);
                                // dd($messge);

                                $order = Order::find($orderId);
                                
                                //Notify Admin of receipt
                                event(new DispatcherOrderRecievedAdminEvent($order));

                                //Notify User of order on the way
                                event(new DispatcherOrderRecievedUserEvent($order));

                                $this->responseData = ResponseMessages::disptcherAcknoledged($user, $order);

                            }
                        }

                        elseif (Str::startsWith($messge, "address-confirm:")) {

                            $parts = explode(":", Str::replace("address-confirm:", "", $messge, false));

                            $confirmation = $parts[0];
                            $orderId = $parts[1];
                            $easylunchRequest = $parts[2] ?? false;

                            $order = Order::find($orderId);

                            if($easylunchRequest) {
                                $order->transaction->update(['method' => Utils::PAYMENT_METHOD_EASY_LUNCH]);
                            }

                            if($confirmation === 'yes') {

                                $method = $order->transaction->method;

                                if ($method === Utils::PAYMENT_METHOD_WALLET or $method === Utils::PAYMENT_METHOD_EASY_LUNCH) {

                                    //Perform checkout
                                    $order = $orderService->performCheckout($orderId, ($easylunchRequest) ?  Utils::PAYMENT_METHOD_EASY_LUNCH : $user->wallet->id, $method);

                                    //Notify user that their order has been placed
                                    $this->responseData = ResponseMessages::userOrderPlaced($customerPhoneNumber, $order);
                                }
                                else {
                                    
                                 $order = $orderService->performCheckout($orderId, $method, $method);
                                    $this->responseData = ResponseMessages::userPayMethod($customerPhoneNumber, $orderId, $method);
                                }

                                 //send notification to admin
                                 event(new OrderPlacedEvent($user, $order, $method));

                            }
                            else {

                                $order->updated_at = now();
                                $order->save();

                                $this->responseData = ResponseMessages::enterOrderDeliveryAddress($customerPhoneNumber);
                            }
                        }
                        
                        elseif (Str::startsWith($messge, "data-confirm:")) {

                            $parts = explode(":", Str::replace("data-confirm:", "", $messge));

                            $dataPlan = DataService::get(array('id' => $parts[0]));

                            $dataService = new DataService($user);
                            $dataService->confirmDataPurchase($dataPlan, $parts[1]);
                            $status = $dataService->getStatus();

                            $this->responseData = ResponseMessages::sendDataPurchaseResponse($customerPhoneNumber, $dataPlan, $status);
                        } 
                        
                        elseif (str_starts_with($messge, "Order from ")) {

                            $vendorId = str_replace("Order from ", "", $messge);
                            $vendor = Vendor::find($vendorId);

                            if ($vendor) {
                                $this->responseData = ResponseMessages::vendorCatalog($customerPhoneNumber, $vendor, false);
                            }
                        } 
                        
                        elseif (str_starts_with($messge, "button-easy-lunch-sub-pay-now:")) {

                            $easyLunchSubId = str_replace("button-easy-lunch-sub-pay-now:", "", $messge);

                            $easyluchSub = EasyLunchSubscribers::find($easyLunchSubId);

                            $easyluchSubOrder = $orderService->findUserCurrentOrder($user, "Easy lunch package $easyluchSub->package_type - N$easyluchSub->amount", $easyluchSub->amount);

                            $this->responseData = ResponseMessages::currentOrder($customerPhoneNumber, $easyluchSubOrder);
                        } 
                        
                        elseif (str_starts_with($messge, "button-order-checkout")) {

                            $parts = explode(":", str_replace("order-confirm-", "", $messge));

                            $easylunchRequest = $parts[2] ?? false;
                            $orderId = $parts[1];

                            $easyLunchService = new EasyLunchService();
                            $easylunchRequest = ($easyLunchService->isActive($user) and $easylunchRequest);


                            $order = Order::find($orderId);

                            if(Str::startsWith($order->description, 'Easy lunch package')) {
                                $easylunchRequest = false;
                            }

                            if ($order) {
                                $this->responseData = ResponseMessages::confirmOrderCheckout($customerPhoneNumber, $order, $easylunchRequest);
                            }

                        } 
                        
                        elseif (str_starts_with($messge, "order-confirm-")) {

                            $parts = explode(":", str_replace("order-confirm-", "", $messge));

                            $response = $parts[0];
                            $orderId = $parts[1];
                            $easylunchRequest = $parts[2] ?? false;
                            $order = Order::find($orderId);

                            if ($response == 'yes') {

                                if ($easylunchRequest) {

                                    $this->responseData = ResponseMessages::enterOrderDeliveryAddress($customerPhoneNumber, $easylunchRequest);
                                    
                                } else {

                                    $this->responseData = ResponseMessages::chooseWallet($customerPhoneNumber, $order);
                                }
                            } else {

                                $orderService->cancelOrder($orderId);

                                $this->responseData = ResponseMessages::dashboardMessage($user);
                            }
                        }
                    }
                }
            }

            //For unregistered users
            else {

                if ($incomingMessageType === Utils::TEXT) {

                    //process text based message
                    //Get the text
                    $text = strtolower($incomingMessage['text']['body']);

                    $authService = new AuthService();

                    //Create new user
                    $userService->createUser(['phone' => $customerPhoneNumber]);

                    if (str_starts_with($text, 'ref-')) {

                        $userService->updateUserParam([
                            'referred_by' => strtoupper($text)
                        ], $customerPhoneNumber);

                        $this->responseData = ResponseMessages::welcomeMessage($customerPhoneNumber, false);
                    }

                    //Check if its a verification code
                    elseif (substr($text, 0, 5) == 'veri-') {

                        if ($user and $authService->verifyCode($customerPhoneNumber, strtoupper($text))) {

                            //Create wallet
                            $walletService = new WalletService();
                            $walletService->createWallet($user);

                            //Update user email
                            $userService->updateUserParam(['email' => $user->temp_email], $customerPhoneNumber);

                            $this->responseData = ResponseMessages::dashboardMessage($user);
                        } 
                        
                        else $this->responseData = ResponseMessages::invalidTokenMessge($customerPhoneNumber, strtoupper($text), false);
                    } 
                    
                    elseif (filter_var($text, FILTER_VALIDATE_EMAIL)) {

                        //update email address
                        $userService->updateUserParam([
                            'temp_email' => $text,
                            'referral_code' => "ref-" . strtoupper(str_replace(".", "-", substr($text, 0, strpos($text, "@"))) . Random::generate(10, 'a-z'))
                        ], $customerPhoneNumber);

                        //ask user to enter name
                        $this->responseData = ResponseMessages::enterNameMessage($text, $customerPhoneNumber, false);
                    } 
                    
                    elseif (preg_match("([aA-zZ] [aA-zZ])", $text)) {

                        //check for temp_email
                        if ($user and $user->temp_email) {

                            $names = explode(" ", $text);
                            $firstName = ucfirst($names[0]);
                            $lastName = ucfirst($names[1]);

                            //update names
                            $userService->updateUserParam([
                                'first_name' => $firstName,
                                'last_name' => $lastName
                            ], $customerPhoneNumber);

                            //Todo: send email verification notification to email
                            $confirmationToken = $authService->generateHash("$firstName $lastName", $user->temp_email);

                            //Build a verification link to be sent to new user
                            $verificationUrl = route('user.verify', ['email' => $user->temp_email, 'hash' => $confirmationToken->token]);

                            //Initialize notification service and send verification message
                            $notificationService = new NotificationService();
                            $notificationService->sendEmail($user->temp_email, new EmailVerification("$firstName $lastName", $confirmationToken->veri_token, $verificationUrl));

                            //Notify user of verification email sent
                            $this->responseData = ResponseMessages::sendVerificationNotificationMessage(
                                $user->temp_email,
                                $customerPhoneNumber,
                                false
                            );
                        } 
                        
                        else $this->responseData = ResponseMessages::errorMessage($customerPhoneNumber, false, false);
                    } 
                    
                    elseif (in_array($text, Utils::USER_INPUTS_GREETINGS)) {

                        $this->responseData = ResponseMessages::welcomeMessage($customerPhoneNumber, false);
                    } 
                    
                    else $this->responseData = ResponseMessages::errorMessage($customerPhoneNumber, false, false);
                } 
                
                elseif ($incomingMessageType === Utils::INTERACTIVE) {

                    $interactiveMessage = $incomingMessage['interactive'];

                    if ($interactiveMessage['type'] === Utils::BUTTON_REPLY) {

                        switch ($interactiveMessage['button_reply']['id']) {

                            case Utils::BUTTONS_START_AGAIN: {

                                    //reset user registration
                                    $userService->updateUserParam(
                                        [
                                            "temp_email" => null,
                                            "referred_by" => null,
                                            "referral_code" => null
                                        ],

                                        $customerPhoneNumber
                                    );

                                    $this->responseData = ResponseMessages::letsBegin($customerPhoneNumber);
                                    break;
                                }

                            case Utils::BUTTONS_VIEW_SERVICES: {
                                    $this->responseData = ResponseMessages::viewOurServices($customerPhoneNumber);
                                    break;
                                }

                            case Utils::BUTTONS_GUEST_BEGIN: {
                                    $this->responseData = ResponseMessages::letsBegin($customerPhoneNumber);
                                    break;
                                }


                            default:
                                # code...
                                break;
                        }
                    }
                }
            }
        }

        //When user is verifying a confirmation code
        elseif ($this->origin === Utils::ORIGIN_VERIFICATION) {

            $customerPhoneNumber = $this->data['phone'];

            //Get User
            $user = $userService->getUserByPhoneNumber($this->data['phone']);

            if ($user) {
                
                //Send user dashboard message
                $this->responseData = ResponseMessages::dashboardMessage($user);
            }

        } elseif ($this->origin == Utils::ADMIN_EVENTS) {

            $eventType = $this->data['type'];

            if ($eventType === Utils::ADMIN_USER_ORDER_NOTIFY) {

                $order = $this->data['order'];

                $customer = $this->data['customer'];
                $method = $this->data['method'];

                $this->responseData = ResponseMessages::notifyAdmin($customer, $order, $method);

            } 

            elseif($eventType === Utils::ADMIN_PROCESS_USER_ORDER_DISPATCHER_RECIEVED_ADMIN) {

                $order = $this->data['order'];
                $this->responseData = ResponseMessages::orderOnTheWayNotifyerAdmin($order);
                
            }

            elseif($eventType === Utils::ADMIN_PROCESS_USER_ORDER_DISPATCHER_RECIEVED_USER) {

                $order = $this->data['order'];
                $this->responseData = ResponseMessages::orderOnTheWayNotifyer($order);
                
            }
            
            elseif ($eventType === Utils::ADMIN_PROCESS_USER_ORDER) {
                
               $contact = $this->data['customerPhone'];
               $order = $this->data['order']; 
               $dispatcherPhone = $this->data['dispatcher'];
               $fee = $this->data['fee'];
               $location = Errand::where('order_id', $order->id)->first()->delivery_address;

               //Notify user that order has been processed
               $transactionService = new TransactionService();
               $transactionService->updateTransaction($order->transaction, ['status' => Utils::TRANSACTION_STATUS_SUCCESS]);

               //Update order status
               $order->status = Utils::ORDER_STATUS_PROCESSED;
               $order->save();

               //Update invoice 
               $orderInvoice = $order->transaction->orderInvoice;
               $orderInvoice->url = OrderService::getOrderInvoice($order);
               $orderInvoice->save();
               
               $this->responseData = ResponseMessages::userOrderProcessed($contact, $order, $dispatcherPhone, $fee);

                //Dispatch message to a dispatcher rider
                event(new OrderAssignedToDispatcherEvent($order, $dispatcherPhone, $fee, $contact, $location));

                //send reciept to user
            } 
            
            elseif ($eventType === Utils::ADMIN_WALLET_EVENTS) {

                $wallet = $this->data['wallet'];


                if ($wallet == Utils::ADMIN_WALLET_EASY_ACCESS) {

                    $adminResponse = Http::withHeaders(["AuthorizationToken" => env('EASY_ACCESS_TOKEN'), "cache-control" => "no-cache"])
                        ->get("https://easyaccess.com.ng/api/wallet_balance.php");


                    $requestResponseData = $adminResponse->json();
                    $currenntBalance = $requestResponseData['balance'];
                    $checkDate = $requestResponseData['checked_date'];
                    $accounts = [];

                    for ($count = 1; $count <= 10; $count++) {
                        if ($requestResponseData["funding_acctno$count"] ?? false) {
                            array_push($accounts, new VirtualAccount($requestResponseData["funding_bank$count"], $requestResponseData["funding_acctno$count"], $requestResponseData["funding_acctname"]));
                        }
                    }

                    $admin = User::where('role', Utils::USER_ROLE_ADMIN)->first();
                    $this->responseData = ResponseMessages::walletLowNotification($admin, $accounts, $currenntBalance, $checkDate, $wallet);
                }
            }

            elseif($eventType === Utils::ADMIN_PROCESS_USER_ORDER_ASSIGN_DISPATCHER) {

               $contact = $this->data['customerPhone'];
               $order = $this->data['order']; 
               $dispatcherPhone = $this->data['dispatcher'];
               $fee = $this->data['fee'];
               $location = $this->data['location'];

               $dispatcher = UserService::getAdmin($dispatcherPhone, Utils::USER_ROLE_DISPATCH_RIDER);

               
               if($dispatcher) {


                $this->responseData = ResponseMessages::messageDispatcher(
                    $order,
                    $fee,
                    $location,
                    $contact,
                    $dispatcher
                );

               }

            }

           

        } else {
            //send error message
        }
    }

    public function sendResponse()
    {
        $whatsAppId = env('WHATSAPP_PHONE_NUMBER_ID');
        $whatsApiVersion = env('WHATSAPP_API_VERSION');

        Http::withToken(env('WHATSAPP_ACCESS_KEY'))
            ->withHeaders(['Content-type' => 'application/json'])
            ->post("https://graph.facebook.com/$whatsApiVersion/$whatsAppId/messages", $this->responseData);


    }
    
    private function cleanMessage($interactiveMessageId)
    {
        return substr($interactiveMessageId, 1, strlen($interactiveMessageId) - 2);
    }

    private function getUserTransactions($customerPhoneNumber, User $user, int $nextPage = 0)
    {
        $transactionService = new TransactionService();
        $userTransactions = $transactionService->fetchUserTransaction($user, $nextPage);

        return ResponseMessages::showUserTransactionHistory($customerPhoneNumber, $userTransactions);
    }

    private function getEasylunchHome(User $user) {

        //Check if this user is already subscribed
        $easyLunchService = new EasyLunchService();
        $subscriptions = $easyLunchService->getSubscriptions($user);

        return  ($subscriptions->count() > 0) ? 
                ResponseMessages::viewEasyLunchSubscriptions($user, $subscriptions) :
                ResponseMessages::easyLunchHome($user->phone);
    }
}
