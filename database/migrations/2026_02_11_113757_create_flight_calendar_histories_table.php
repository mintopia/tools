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
        Schema::create('flight_calendar_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('flight_calendar_id')->constrained()->onDelete('cascade');
            $table->date('date');
            $table->decimal('highest_price_gbp', 10, 2);
            $table->decimal('average_price_gbp', 10, 2);
            $table->integer('flight_count');
            $table->timestamp('created_at');

            $table->unique(['flight_calendar_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('flight_calendar_histories');
    }
};
