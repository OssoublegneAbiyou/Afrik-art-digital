<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('artist_portfolios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('artist_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('artist_portfolio_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('artist_portfolio_id')->constrained('artist_portfolios')->cascadeOnDelete();
            $table->foreignId('illustration_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('position')->default(0);
            $table->string('theme')->default('pulse');
            $table->string('music')->default('abidjan');
            $table->text('description')->nullable();
            $table->string('guide_audio_path')->nullable();
            $table->unsignedBigInteger('guide_audio_size_bytes')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('artist_portfolio_items');
        Schema::dropIfExists('artist_portfolios');
    }
};
