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

            $table->string('transaction_reference')->unique();
            $table->string('method');
            $table->string('description');
            $table->string('payment_reference')->nullable(true);
            $table->string('status')->default('PENDING');
            $table->string('date');

            $table->unsignedBigInteger('user_id');

            $table->decimal('amount', 8, 2, true);

            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete()->cascadeOnUpdate();

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
