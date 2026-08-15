<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media_assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('type', 16)->index();
            $table->string('original_name');
            $table->string('disk', 32)->default('local');
            $table->string('path')->unique();
            $table->string('mime_type', 100);
            $table->string('extension', 16);
            $table->unsignedBigInteger('size_bytes');
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->string('orientation', 16)->nullable()->index();
            $table->string('checksum', 64)->index();
            $table->string('processing_status', 32)->default('processing')->index();
            $table->text('processing_error')->nullable();
            $table->string('approval_status', 32)->default('pending_approval')->index();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'type']);
        });

        Schema::create('media_asset_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('media_asset_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('event', 32)->index();
            $table->string('description')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['media_asset_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_asset_histories');
        Schema::dropIfExists('media_assets');
    }
};
