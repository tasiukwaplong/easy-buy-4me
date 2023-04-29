<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            
            $table->id();

            $table->string('transaction_reference');
            $table->string('method');
            $table->string('payment_reference')->nullable(true);
            $table->string('status')->default('PENDING');

            $table->decimal('amount', 8, 2, true);

            $table->date('date');

            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
