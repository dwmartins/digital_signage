<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('states', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->char('code', 2)->unique();
            $table->string('status', 16)->default('active')->index();
            $table->timestamps();
        });

        Schema::create('cities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('state_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->string('status', 16)->default('active')->index();
            $table->timestamps();

            $table->unique(['state_id', 'name']);
            $table->index(['state_id', 'status']);
        });

        Schema::create('neighborhoods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('city_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->string('status', 16)->default('active')->index();
            $table->timestamps();

            $table->unique(['city_id', 'name']);
            $table->index(['city_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('neighborhoods');
        Schema::dropIfExists('cities');
        Schema::dropIfExists('states');
    }
};
