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
            $table->boolean('is_admin')->default(false)->after('account_type');
        });

        DB::table('users')
            ->where('email', 'ad@admin')
            ->update(['is_admin' => true]);

        Schema::create('featured_selections', function (Blueprint $table) {
            $table->id();
            $table->date('featured_for')->unique();
            $table->foreignId('artist_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('writer_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('featured_selections');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_admin');
        });
    }
};
