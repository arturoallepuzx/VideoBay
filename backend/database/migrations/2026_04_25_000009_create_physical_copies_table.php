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
        Schema::create('physical_copies', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('movie_id')->constrained('movies')->restrictOnDelete();
            $table->string('sku', 64);
            $table->string('barcode', 32)->nullable()->index();
            $table->string('format');
            $table->string('region', 16)->nullable();
            $table->string('condition');
            $table->string('cover_photo_url')->nullable();
            $table->integer('price_cents');
            $table->integer('stock_available')->default(0);
            $table->integer('stock_reserved')->default(0);
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->boolean('is_active')->virtualAs('CASE WHEN deleted_at IS NULL THEN 1 ELSE NULL END');
            $table->unique(['sku', 'is_active'], 'physical_copies_sku_active_unique');
            $table->index(['movie_id', 'active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('physical_copies');
    }
};
