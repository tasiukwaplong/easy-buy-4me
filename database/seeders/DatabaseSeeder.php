<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use App\Models\DataPlan;
use App\Models\EasyLunch;
use App\Models\Item;
use App\Models\MonnifyAccount;
use App\Models\Order;
use App\Models\OrderedItem;
use App\Models\User;
use App\Models\Vendor;
use App\Models\Wallet;
use App\Models\whatsapp\Utils;
use App\utils\Helpers;
use Illuminate\Database\Seeder;
use Nette\Utils\Random;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */

    public function run(): void
    {

        User::create([
            'phone' => "2349031514346",
            'first_name' => "Tasiu",
            'last_name' => "TK",
            'is_admin' => true,
            'email' => "tk@gmail.com",
            'temp_email' => "tk@gmail.com",
            'referral_code' => "jjsjssdbsnbnsdnssdnsi",
        ]);

        $user = User::create([
            'phone' => "2347035002025",
            'first_name' => "Ralph",
            'last_name' => "Eze",
            'is_admin' => false,
            'email' => "ralphses@gmail.com",
            'temp_email' => "ralphses@gmail.com",
            'referral_code' => "jjsjssdbsnbssnsdnnsi",
        ]);

        Wallet::create([
            'user_id' => $user->id,
            'balance' => 20000
        ]);

        MonnifyAccount::create([
            'bank' => "Wema Bank",
            'account_name' => "Ralph",
            'account_number' => "5678656787",
            'account_reference' => "jhsbuishiasuhisushsuhuihduihusiua",
            'bank_code' => "035",
            'user_id' => $user->id
        ]);

        $firstVendor = Vendor::create([
            'name' => 'Chicken Republic',
            'phone' => '07035002025',
            'imageUrl' => Utils::ERRAND_BANNAER,
            'description' => "first vendor description",
            'address' => 'Jos Road Lafia, Nasarawa State'
        ]);

        $secondVendor = Vendor::create([
            'name' => 'Madam 10 10 Restaurant',
            'phone' => '07035002025',
            'imageUrl' => Utils::ERRAND_BANNAER,
            'description' => "second vendor description",
            'address' => 'Gandu, near FULafia, Lafia, Nasarawa State'
        ]);

        $firstItem = Item::create([
            'category' => 'Food',
            'item_name' => 'Fried Rice',
            'item_price' => 960.00,
            'short_description' => 'Well prepared fried rice',
            'unit_name' => 'plate',
            'vendor_id' => $firstVendor->id
        ]);

        $firstItem = Item::create([
            'category' => 'Food',
            'item_name' => 'Eba',
            'item_price' => 470.00,
            'short_description' => 'Well prepared eba',
            'unit_name' => 'plate',
            'vendor_id' => $firstVendor->id
        ]);

        $firstItem = Item::create([
            'category' => 'Food',
            'item_name' => 'salad',
            'item_price' => 890.00,
            'short_description' => 'Well prepared salad',
            'unit_name' => 'dish',
            'vendor_id' => $firstVendor->id
        ]);

        $secondItem = Item::create([
            'category' => 'Drinks',
            'item_name' => '35cl Coke drink',
            'item_price' => 200.00,
            'short_description' => 'Chilled coca cola drink',
            'unit_name' => 'bottle',
            'vendor_id' => $secondVendor->id
        ]);

        EasyLunch::create([
            'name' => "basic",
            'cost_per_week' => "4900",
            'cost_per_month' => "19500"
        ]);

        EasyLunch::create([
            'name' => "standard",
            'cost_per_week' => "7900",
            'cost_per_month' => "29500"
        ]);

        $cost = 109;
        $price = round($cost + doubleval(env('DATA_PLAN_PROFIT', 20)));
        //Create MTN Data plans
        DataPlan::create([
            'name' => "500MB SME",
            'network_name' => "MTN", 
            'network_code' => "01", 
            'cost' => 109, 
            'price' => $price,
            'dataplan' => 50, 
            'description' => "500MB (SME) - N$price 30days"
        ]);

        $cost = 217;
        $price = round($cost + doubleval(env('DATA_PLAN_PROFIT', 20)));
        DataPlan::create([
            'name' => "1GB SME",
            'network_name' => "MTN", 
            'network_code' => "01", 
            'cost' => 217, 
            'price' => $price,
            'dataplan' => 51, 
            'description' => "1GB (SME) - N$price 30days"
        ]);

        $cost = 434;
        $price = round($cost + doubleval(env('DATA_PLAN_PROFIT', 20)));
        DataPlan::create([
            'name' => "2GB SME",
            'network_name' => "MTN", 
            'network_code' => "01", 
            'cost' => 434, 
            'price' => $price,
            'dataplan' => 52, 
            'description' => "2GB (SME) - N$price 30days"
        ]);

        $cost = 651;
        $price = round($cost + doubleval(env('DATA_PLAN_PROFIT', 20)));
        DataPlan::create([
            'name' => "3GB SME",
            'network_name' => "MTN", 
            'network_code' => "01", 
            'cost' => 651, 
            'price' => $price,
            'dataplan' => 53, 
            'description' => "3GB (SME) - N$price 30days"
        ]);

        //Glo Data plans

        $cost = 225;
        $price = round($cost + doubleval(env('DATA_PLAN_PROFIT', 20)));
        DataPlan::create([
            'name' => "1GB CG",
            'network_name' => "GLO", 
            'network_code' => "02", 
            'cost' => 225, 
            'price' => $price,
            'dataplan' => 160, 
            'description' => "1GB (CG) - N$price 30days"
        ]);

        $cost = 450;
        $price = round($cost + doubleval(env('DATA_PLAN_PROFIT', 20)));

        DataPlan::create([
            'name' => "2GB CG",
            'network_name' => "GLO", 
            'network_code' => "02", 
            'cost' => 450, 
            'price' => $price,
            'dataplan' => 161, 
            'description' => "2GB (CG) - N$price 30days"
        ]);

        $cost = 53;
        $price = round($cost + doubleval(env('DATA_PLAN_PROFIT', 20)));
        DataPlan::create([
            'name' => "200MB CG",
            'network_name' => "GLO", 
            'network_code' => "02", 
            'cost' => 53, 
            'price' => $price,
            'dataplan' => 158, 
            'description' => "158 => 200MB (CG) - N$price 14days"
        ]);

        $cost = 470.25;
        $price = round($cost + doubleval(env('DATA_PLAN_PROFIT', 20)));

        DataPlan::create([
            'name' => "1.35GB Direct",
            'network_name' => "GLO", 
            'network_code' => "02", 
            'cost' => 470.25, 
            'price' => $price,
            'dataplan' => 59, 
            'description' => "1.35GB (Direct) - N$price 14days"
        ]);


        //Adirtel data plans

        $cost = 220;
        $price = round($cost + doubleval(env('DATA_PLAN_PROFIT', 20)));
        DataPlan::create([
            'name' => "1GB CG",
            'network_name' => "AIRTEL", 
            'network_code' => "03", 
            'cost' => 220, 
            'price' => $price,
            'dataplan' => 107, 
            'description' => "1GB (CG) - N$price 30days"
        ]);

        $cost = 1069.2;
        $price = round($cost + doubleval(env('DATA_PLAN_PROFIT', 20)));
        DataPlan::create([
            'name' => "2GB Direct",
            'network_name' => "AIRTEL", 
            'network_code' => "03", 
            'cost' => 1069.2, 
            'price' => $price,
            'dataplan' => 68, 
            'description' => "2GB (Direct) - N$price 30days"
        ]);

        $cost = 150;
        $price = round($cost + doubleval(env('DATA_PLAN_PROFIT', 20)));
        //9Mobile data plans
        DataPlan::create([
            'name' => "1GB SME",
            'network_name' => "9MOBILE", 
            'network_code' => "04", 
            'cost' => 150, 
            'price' => $price,
            'dataplan' => 128, 
            'description' => "1GB (SME) - N$price 30days"
        ]);

        $cost = 300;
        $price = round($cost + doubleval(env('DATA_PLAN_PROFIT', 20)));
        DataPlan::create([
            'name' => "2GB SME",
            'network_name' => "9MOBILE", 
            'network_code' => "04", 
            'cost' => 300, 
            'price' => $price,
            'dataplan' => 130, 
            'description' => "2GB (SME) - N$price 30days"
        ]);
    }
}
