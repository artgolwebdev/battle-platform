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
        Schema::create('battle_matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('battle_id')->constrained()->cascadeOnDelete();
            $table->integer('round');
            $table->integer('position');
            $table->foreignId('registration1_id')->nullable()->constrained('registrations')->nullOnDelete();
            $table->foreignId('registration2_id')->nullable()->constrained('registrations')->nullOnDelete();
            $table->foreignId('winner_id')->nullable()->constrained('registrations')->nullOnDelete();
            $table->integer('score1')->nullable();
            $table->integer('score2')->nullable();
            $table->string('status')->default('pending'); // pending, completed
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('battle_matches');
    }
};
