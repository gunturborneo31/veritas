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
        Schema::create('greeting_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('greeting_id')->unique()->constrained()->restrictOnDelete();
            $table->foreignId('template_id')->constrained()->restrictOnDelete();
            $table->string('recipient_name');
            $table->string('sender_name')->nullable();
            $table->string('occasion')->nullable();
            $table->json('components');
            $table->json('content');
            $table->string('content_hash', 64);
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('greeting_snapshots');
    }
};
