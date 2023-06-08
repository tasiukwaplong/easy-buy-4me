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
        Schema::create('data_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('network_name');
            $table->string('network_code');
            $table->unsignedDecimal('cost');
            $table->integer('dataplan')->unique();
            $table->string('description');
            $table->unsignedInteger('price');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('data_plans');
    }
};
