<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('external_id')->nullable()->unique();
            $table->string('provider', 64)->nullable();
            $table->string('payment_method', 32)->nullable()->index();
            $table->string('type', 32)->default('charge')->index();
            $table->string('status', 32)->default('pending')->index();
            $table->decimal('amount', 10, 2);
            $table->timestamp('processed_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['invoice_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
