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
        Schema::create('easy_lunch_subscribers', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('easy_lunch_id');

            $table->string('package_type');
            $table->unsignedDecimal('amount');
            $table->unsignedBigInteger('orders_remaining')->nullable();
            $table->date('last_used')->nullable();
            $table->string('last_order')->nullable();
            $table->boolean('paid')->default(false);
            
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreign('last_order')->references('order_id')->on('orders')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreign('easy_lunch_id')->references('id')->on('easy_lunches')->cascadeOnDelete()->cascadeOnUpdate();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('easy_lunch_subscribers');
    }
};
