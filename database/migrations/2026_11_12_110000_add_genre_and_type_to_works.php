<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->string('genre', 32)->nullable()->after('description');
        });

        Schema::table('illustrations', function (Blueprint $table) {
            $table->string('type', 32)->nullable()->after('title');
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropColumn('genre');
        });

        Schema::table('illustrations', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
