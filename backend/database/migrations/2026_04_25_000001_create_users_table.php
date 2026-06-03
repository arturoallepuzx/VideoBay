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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->string('email');
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password_hash');
            $table->string('avatar_url')->nullable();
            $table->string('role')->default('customer');
            $table->json('accessibility_settings')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->boolean('is_active')->virtualAs('CASE WHEN deleted_at IS NULL THEN 1 ELSE NULL END');
            $table->unique(['name', 'is_active'], 'users_name_active_unique');
            $table->unique(['email', 'is_active'], 'users_email_active_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
