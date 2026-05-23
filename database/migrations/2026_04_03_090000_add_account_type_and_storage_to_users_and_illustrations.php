<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('account_type')->default('artist')->after('email');
        });

        DB::table('users')->update(['account_type' => 'artist']);

        Schema::table('illustrations', function (Blueprint $table) {
            $table->unsignedBigInteger('file_size_bytes')->nullable()->after('image_path');
        });
    }

    public function down(): void
    {
        Schema::table('illustrations', function (Blueprint $table) {
            $table->dropColumn('file_size_bytes');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('account_type');
        });
    }
};
