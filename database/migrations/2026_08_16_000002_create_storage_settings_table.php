<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('storage_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 32)->default('media')->unique();
            $table->string('driver', 16)->default('local');
            $table->string('r2_account_id')->nullable();
            $table->string('r2_access_key_id')->nullable();
            $table->text('r2_secret_access_key')->nullable();
            $table->string('r2_bucket')->nullable();
            $table->string('r2_endpoint')->nullable();
            $table->string('aws_access_key_id')->nullable();
            $table->text('aws_secret_access_key')->nullable();
            $table->string('aws_region')->nullable();
            $table->string('aws_bucket')->nullable();
            $table->string('aws_endpoint')->nullable();
            $table->string('aws_url')->nullable();
            $table->boolean('aws_use_path_style_endpoint')->default(false);
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('storage_settings');
    }
};
