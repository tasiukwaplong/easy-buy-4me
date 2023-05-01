<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Seeder;

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
    }
}
