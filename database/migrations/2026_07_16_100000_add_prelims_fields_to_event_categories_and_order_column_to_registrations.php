<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_categories', function (Blueprint $table) {
            $table->enum('current_phase', ['registration', 'prelims', 'bracket', 'complete'])
                ->default('registration')
                ->after('description');
            $table->foreignId('current_prelims_registration_id')
                ->nullable()
                ->after('current_phase')
                ->constrained('registrations')
                ->nullOnDelete();
            $table->boolean('has_prelims')->default(false)->after('current_prelims_registration_id');
        });

        Schema::table('registrations', function (Blueprint $table) {
            $table->integer('order_column')->nullable()->after('bracket_position');
        });
    }

    public function down(): void
    {
        Schema::table('registrations', function (Blueprint $table) {
            $table->dropColumn('order_column');
        });

        Schema::table('event_categories', function (Blueprint $table) {
            $table->dropConstrainedForeignId('current_prelims_registration_id');
            $table->dropColumn(['current_phase', 'has_prelims']);
        });
    }
};