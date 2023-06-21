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
        Schema::create('easy_lunches', function (Blueprint $table) {
            
            $table->id();

            $table->string('name');
            $table->string('description')->nullable();

            $table->unsignedDecimal('cost_per_week');
            $table->unsignedDecimal('cost_per_month');

            $table->boolean('is_active')->default(true);

            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('easy_lunches');
    }
};
