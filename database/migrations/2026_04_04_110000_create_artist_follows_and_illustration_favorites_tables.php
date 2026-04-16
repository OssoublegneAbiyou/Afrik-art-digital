<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('artist_follows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('artist_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['user_id', 'artist_id']);
        });

        Schema::create('illustration_favorites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('illustration_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['user_id', 'illustration_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('illustration_favorites');
        Schema::dropIfExists('artist_follows');
    }
};
