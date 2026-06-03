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
        Schema::create('movies', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->bigInteger('tmdb_id')->nullable();
            $table->string('imdb_id', 20)->nullable()->index();
            $table->string('title');
            $table->string('original_title')->nullable();
            $table->text('overview')->nullable();
            $table->date('release_date')->nullable();
            $table->smallInteger('runtime_minutes')->nullable();
            $table->string('original_language', 10)->nullable();
            $table->string('poster_path')->nullable();
            $table->string('backdrop_path')->nullable();
            $table->decimal('tmdb_rating', 4, 2)->nullable();
            $table->timestamp('cached_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->boolean('is_active')->virtualAs('CASE WHEN deleted_at IS NULL THEN 1 ELSE NULL END');
            $table->unique(['tmdb_id', 'is_active'], 'movies_tmdb_active_unique');
            $table->index('title');
            $table->fullText(['title', 'original_title', 'overview'], 'movies_fulltext_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('movies');
    }
};
