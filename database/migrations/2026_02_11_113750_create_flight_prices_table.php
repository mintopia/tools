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
        Schema::create('flight_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('flight_calendar_id')->constrained()->onDelete('cascade');
            $table->date('date');
            $table->string('flight_number');
            $table->time('departure_time');
            $table->time('arrival_time');
            $table->decimal('price_gbp', 10, 2);
            $table->string('currency', 3)->default('GBP');
            $table->json('raw_data');
            $table->timestamp('created_at');

            $table->unique(['flight_calendar_id', 'date', 'flight_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('flight_prices');
    }
};
