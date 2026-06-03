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
        Schema::create('video_files', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('movie_id')->constrained('movies')->cascadeOnDelete();
            $table->string('original_filename')->nullable();
            $table->string('original_format', 16)->nullable();
            $table->string('original_path')->nullable();
            $table->string('processed_path')->nullable();
            $table->string('mime_type', 64)->default('video/mp4');
            $table->integer('duration_seconds')->nullable();
            $table->bigInteger('file_size_bytes')->nullable();
            $table->string('audio_language', 10)->nullable();
            $table->string('processing_status')->default('pending');
            $table->text('processing_error')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['movie_id', 'processing_status']);
            $table->index('processing_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('video_files');
    }
};
