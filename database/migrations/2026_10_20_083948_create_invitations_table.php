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
        Schema::create('invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('greeting_id')->unique()->constrained()->cascadeOnDelete();
            $table->boolean('rsvp_enabled')->default(true);
            $table->timestamp('rsvp_deadline')->nullable();
            $table->boolean('wishes_enabled')->default(true);
            $table->boolean('gifts_enabled')->default(true);
            $table->unsignedTinyInteger('default_guest_quota')->default(2);
            $table->text('broadcast_message')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invitations');
    }
};
