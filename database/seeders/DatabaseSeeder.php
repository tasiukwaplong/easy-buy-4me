<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use App\Models\User;
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
        User::create([
            'phone' => "07035002025",
            'first_name' => 'Raphael',
            'last_name' => 'Eze',
            'email' => 'eze.raph@gmail.com',
            'temp_email' => 'eze.raph@gmail.com',
        ]);
    }
}
