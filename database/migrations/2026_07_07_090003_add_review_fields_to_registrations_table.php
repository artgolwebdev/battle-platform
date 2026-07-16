<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('registrations', function (Blueprint $table) {
            $table->string('status')->default('pending')->after('email');
            $table->integer('seed')->nullable()->after('status');
            $table->string('bracket_position')->nullable()->after('seed');
        });
    }

    public function down(): void
    {
        Schema::table('registrations', function (Blueprint $table) {
            $table->dropColumn(['status', 'seed', 'bracket_position']);
        });
    }
};
