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
            $table->string('name');
            $table->string('code', 64)->unique();
            $table->string('brand')->nullable();
            $table->string('model')->nullable();
            $table->decimal('screen_size', 5, 1)->nullable();
            $table->unsignedSmallInteger('resolution_width')->default(1920);
            $table->unsignedSmallInteger('resolution_height')->default(1080);
            $table->string('status', 32)->default('active')->index();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('screens');
    }
};
