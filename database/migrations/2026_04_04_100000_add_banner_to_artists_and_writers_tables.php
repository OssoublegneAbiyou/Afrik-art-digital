<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('artists', function (Blueprint $table) {
            $table->string('banner_path')->nullable()->after('bio');
            $table->unsignedBigInteger('banner_size_bytes')->default(0)->after('banner_path');
        });

        Schema::table('writers', function (Blueprint $table) {
            $table->string('banner_path')->nullable()->after('bio');
            $table->unsignedBigInteger('banner_size_bytes')->default(0)->after('banner_path');
        });
    }

    public function down(): void
    {
        Schema::table('artists', function (Blueprint $table) {
            $table->dropColumn(['banner_path', 'banner_size_bytes']);
        });

        Schema::table('writers', function (Blueprint $table) {
            $table->dropColumn(['banner_path', 'banner_size_bytes']);
        });
    }
};
