<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('illustrations', function (Blueprint $table) {
            $table->text('description')->nullable()->after('type');
            $table->json('tags')->nullable()->after('description');
            $table->unsignedInteger('view_count')->default(0)->after('file_size_bytes');
            // null = brouillon ; date future = publication programmée ; date passée = publiée.
            $table->timestamp('published_at')->nullable()->after('view_count');
            $table->index('published_at');
        });

        Schema::table('documents', function (Blueprint $table) {
            $table->unsignedInteger('view_count')->default(0)->after('download_count');
            $table->timestamp('published_at')->nullable()->after('view_count');
            $table->index('published_at');
        });

        // Tout ce qui était en ligne le reste.
        DB::table('illustrations')->whereNull('published_at')->update(['published_at' => DB::raw('created_at')]);
        DB::table('documents')->whereNull('published_at')->update(['published_at' => DB::raw('created_at')]);

        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');

        Schema::table('documents', function (Blueprint $table) {
            $table->dropIndex(['published_at']);
            $table->dropColumn(['view_count', 'published_at']);
        });

        Schema::table('illustrations', function (Blueprint $table) {
            $table->dropIndex(['published_at']);
            $table->dropColumn(['description', 'tags', 'view_count', 'published_at']);
        });
    }
};
