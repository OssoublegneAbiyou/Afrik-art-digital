<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('artist_portfolio_items', function (Blueprint $table) {
            $table->string('custom_music_path')->nullable()->after('music');
            $table->unsignedBigInteger('custom_music_size_bytes')->default(0)->after('custom_music_path');
        });
    }

    public function down(): void
    {
        Schema::table('artist_portfolio_items', function (Blueprint $table) {
            $table->dropColumn(['custom_music_path', 'custom_music_size_bytes']);
        });
    }
};
