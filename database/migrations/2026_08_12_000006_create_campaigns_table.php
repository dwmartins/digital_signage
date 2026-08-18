<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('playback_mode', 16)->default('sequential');
            $table->string('status', 32)->default('active')->index();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });

        Schema::create('campaign_media_asset', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained()->cascadeOnDelete();
            $table->foreignId('media_asset_id')->constrained()->restrictOnDelete();
            $table->unsignedSmallInteger('position')->default(1);
            $table->unsignedInteger('display_duration_seconds')->nullable();
            $table->timestamps();

            $table->unique(['campaign_id', 'media_asset_id']);
        });

        Schema::create('campaign_display_point', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained()->cascadeOnDelete();
            $table->foreignId('display_point_id')->constrained()->restrictOnDelete();
            $table->timestamps();

            $table->unique(['campaign_id', 'display_point_id']);
        });

        Schema::create('media_asset_distributions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained()->cascadeOnDelete();
            $table->foreignId('media_asset_id')->constrained()->cascadeOnDelete();
            $table->foreignId('display_point_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('position')->default(1);
            $table->string('status', 32)->default('pending')->index();
            $table->timestamp('processing_started_at')->nullable();
            $table->timestamp('downloaded_at')->nullable();
            $table->timestamp('distributed_at')->nullable();
            $table->timestamp('last_attempt_at')->nullable();
            $table->timestamp('last_reported_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->string('downloaded_checksum', 64)->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->unique(
                ['campaign_id', 'media_asset_id', 'display_point_id'],
                'media_distribution_unique',
            );
            $table->index(['display_point_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_asset_distributions');
        Schema::dropIfExists('campaign_display_point');
        Schema::dropIfExists('campaign_media_asset');
        Schema::dropIfExists('campaigns');
    }
};
