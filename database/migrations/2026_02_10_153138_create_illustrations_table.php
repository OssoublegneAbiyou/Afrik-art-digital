<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('illustrations', function (Blueprint $table) {
    $table->id();
    $table->foreignId('artist_id')->constrained()->onDelete('cascade'); // lien avec artiste
    $table->string('title');
    $table->string('image_path'); // chemin de l'image
    $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('illustrations');
    }
};
