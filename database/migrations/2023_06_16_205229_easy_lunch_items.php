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
        Schema::create('easy_lunch_items', function(Blueprint $table) {

            $table->id();

            $table->unsignedBigInteger('easy_lunch_id');
            $table->unsignedBigInteger('item_id');
        
            $table->foreign('easy_lunch_id')->references('id')->on('easy_lunches')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreign('item_id')->references('id')->on('items')->cascadeOnDelete()->cascadeOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('easy_lunch_items');
    }
};
