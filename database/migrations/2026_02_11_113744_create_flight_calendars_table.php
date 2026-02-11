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
        Schema::create('flight_calendars', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('from_airport', 3);
            $table->string('to_airport', 3);
            $table->json('airlines');
            $table->integer('max_stops')->default(0);
            $table->boolean('is_active')->default(true);
            $table->integer('days_ahead')->default(30);
            $table->timestamp('last_fetched_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('flight_calendars');
    }
};
