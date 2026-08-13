<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('players', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 64)->unique();
            $table->string('hostname')->nullable();
            $table->string('brand')->nullable();
            $table->string('model')->nullable();
            $table->string('operating_system')->nullable();
            $table->string('architecture', 32)->nullable();
            $table->unsignedInteger('memory_mb')->nullable();
            $table->unsignedInteger('storage_mb')->nullable();
            $table->string('status', 32)->default('active')->index();
            $table->timestamp('last_seen_at')->nullable()->index();
            $table->ipAddress('ip_address')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('players');
    }
};
