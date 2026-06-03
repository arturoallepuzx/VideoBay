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
        Schema::create('barcode_lookups', function (Blueprint $table) {
            $table->string('barcode', 32)->primary();
            $table->foreignId('movie_id')->nullable()->constrained('movies')->nullOnDelete();
            $table->string('resolved_via');
            $table->boolean('confirmed')->default(false);
            $table->timestamps();

            $table->index('movie_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('barcode_lookups');
    }
};
