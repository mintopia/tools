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
        Schema::table('flight_calendars', function (Blueprint $table) {
            $table->string('from_airport', 100)->change();
            $table->string('to_airport', 100)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('flight_calendars', function (Blueprint $table) {
            $table->string('from_airport', 3)->change();
            $table->string('to_airport', 3)->change();
        });
    }
};
