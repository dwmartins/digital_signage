<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('screens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('establishment_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('code', 64)->unique();
            $table->string('location')->nullable();
            $table->string('brand')->nullable();
            $table->string('model')->nullable();
            $table->decimal('screen_size', 5, 1)->nullable();
            $table->string('orientation', 16)->default('landscape');
            $table->unsignedSmallInteger('resolution_width')->default(1920);
            $table->unsignedSmallInteger('resolution_height')->default(1080);
            $table->string('status', 32)->default('active')->index();
            $table->timestamp('last_seen_at')->nullable()->index();
            $table->ipAddress('ip_address')->nullable();
            $table->unsignedSmallInteger('heartbeat_interval')->default(60);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['establishment_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('screens');
    }
};
