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
        Schema::create('order_invoices', function (Blueprint $table) {

            $table->id();

            $table->string('customer_name', 45);
            $table->string('type', 50);
            $table->string('invoice_no', 64)->unique();
            $table->string('url')->nullable();

            $table->integer('status');

            $table->unsignedBigInteger('transaction_id');

            $table->foreign('transaction_id')->references('id')->on('transactions')->cascadeOnDelete()->cascadeOnUpdate();
            $table->timestamps();
            
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_invoices');
    }
};
