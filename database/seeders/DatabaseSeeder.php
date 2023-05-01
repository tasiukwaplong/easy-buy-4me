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
    protected $fillable = [
        'phone',
        'first_name',
        'last_name',
        'email',
        'temp_email',
    ];
    public function run(): void
    {
        $user = User::create([
            'phone' => "07035002025",
            'first_name' => 'Raphael',
            'last_name' => 'Eze',
            'email' => 'eze.raph@gmail.com',
            'temp_email' => 'eze.raph@gmail.com',
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

        $secondItem = Item::create([
            'category' => 'Drinks',
            'item_name' => '35cl Coke drink',
            'item_price' => 200.00,
            'short_description' => 'Chilled coca cola drink',
            'unit_name' => 'bottle',
            'vendor_id' => $secondVendor->id
        ]);

        $firstOrder = Order::create([
            'order_id' => strtoupper(Random::generate()),
            'description' => "first order description",
            'total_amount' => 2000,
            'status' => Helpers::ORDER_STATUS['delivered'],
            'user_id' => $user->id
        ]);

        OrderedItem::create([
            'item_id' => $firstItem->id,
            'quantity' => 2,
            'order_id' => $firstOrder->id
        ]);

        OrderedItem::create([
            'item_id' => $secondItem->id,
            'quantity' => 4,
            'order_id' => $firstOrder->id
        ]);
    }
}
