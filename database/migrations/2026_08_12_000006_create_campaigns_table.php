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
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('status', 32)->default('draft')->index();
            $table->timestamp('approved_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });

        Schema::create('campaign_category', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->timestamps();

            $table->unique(['campaign_id', 'category_id']);
        });

        Schema::create('campaign_media_asset', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained()->cascadeOnDelete();
            $table->foreignId('media_asset_id')->constrained()->restrictOnDelete();
            $table->unsignedSmallInteger('position')->default(1);
            $table->unsignedInteger('display_duration_seconds')->nullable();
            $table->timestamps();

            $table->unique('campaign_id');
            $table->unique(['campaign_id', 'media_asset_id']);
        });

        Schema::create('campaign_display_point', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained()->cascadeOnDelete();
            $table->foreignId('display_point_id')->constrained()->restrictOnDelete();
            $table->timestamps();

            $table->unique(['campaign_id', 'display_point_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_display_point');
        Schema::dropIfExists('campaign_media_asset');
        Schema::dropIfExists('campaign_category');
        Schema::dropIfExists('campaigns');
    }
};
