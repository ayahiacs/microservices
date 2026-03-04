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
        Schema::create('employees', function (Blueprint $table) {
            // use ULID primary key for distributed unique identifiers
            $table->ulid('id')->primary();

            // common employee fields
            $table->string('first_name');
            $table->string('last_name');
            $table->decimal('salary_per_annum', 15, 2);
            $table->string('country');

            // country-specific data stored in a JSON column for flexibility
            $table->json('country_data')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
