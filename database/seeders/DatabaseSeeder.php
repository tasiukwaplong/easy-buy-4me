<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use App\Models\Item;
use App\Models\Order;
use App\Models\OrderedItem;
use App\Models\User;
use App\Models\Vendor;
use App\Models\Wallet;
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
        $user = User::where('phone', '2347035002025')->first();

        User::create([
            'phone' => "2349122353809",
            'first_name' => "Tasiu",
            'last_name' => "TK",
            'is_admin' => true,
            'email' => "tk@gmail.com",
            'temp_email' => "tk@gmail.com",
            'referral_code' => "jjsjssdbsnbnsdnnsi",
        ]);

        Wallet::create([
            'bank' => 'Wema Bank',
            'account_name' => 'Rap',
            'account_number' => '5000383664',
            'account_reference' => 'ezeraphgmailcomi0bv7j',
            'bank_code' => '035',
            'balance' => 2000.00,
            'user_id' => $user->id
        ]);

        $firstVendor = Vendor::create([
            'name' => 'Chicken Republic',
            'phone' => '07035002025',
            'imageUrl' => "",
            'description' => "first vendor description",
            'address' => 'Jos Road Lafia, Nasarawa State'
        ]);

        $secondVendor = Vendor::create([
            'name' => 'Madam 10 10 Restaurant',
            'phone' => '07035002025',
            'imageUrl' => "",
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
    }
}
