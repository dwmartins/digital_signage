<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('establishments', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('legal_name')->nullable();
            $table->string('document', 32)->unique();
            $table->string('phone', 32)->nullable();
            $table->string('email')->nullable();
            $table->string('contact_name')->nullable();
            $table->string('address');
            $table->string('number', 32)->nullable();
            $table->string('complement')->nullable();
            $table->foreignId('city_id')->constrained()->restrictOnDelete();
            $table->foreignId('neighborhood_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('zip_code', 16)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('status', 32)->default('active')->index();
            $table->text('opening_hours')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['city_id', 'neighborhood_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('establishments');
    }
};
