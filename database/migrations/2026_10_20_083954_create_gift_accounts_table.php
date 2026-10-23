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
        Schema::create('gift_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('greeting_id')->constrained()->cascadeOnDelete();
            $table->string('kind', 16);
            $table->string('provider', 40)->nullable();
            $table->string('account_number', 40)->nullable();
            $table->string('account_holder', 80)->nullable();
            $table->text('address')->nullable();
            $table->unsignedTinyInteger('position')->default(1);
            $table->timestamps();

            $table->index(['greeting_id', 'position']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gift_accounts');
    }
};
