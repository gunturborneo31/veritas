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
        Schema::create('greetings', function (Blueprint $table) {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('draft_token_hash', 64)->nullable()->index();
            $table->foreignId('template_id')->constrained()->restrictOnDelete();
            $table->string('type')->default('ucapan');
            $table->string('status')->default('draf');
            $table->string('slug')->nullable()->unique();
            $table->string('recipient_name')->nullable();
            $table->string('sender_name')->nullable();
            $table->string('occasion')->nullable();
            $table->json('content')->nullable();
            $table->string('preview_token_hash', 64)->nullable();
            $table->timestamp('preview_expires_at')->nullable();
            $table->timestamp('last_edited_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['status', 'last_edited_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('greetings');
    }
};
