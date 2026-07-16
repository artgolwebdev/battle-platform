<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('registration_fields', function (Blueprint $table) {
            $table->foreignId('event_category_id')
                ->nullable()
                ->after('event_id')
                ->constrained('event_categories')
                ->cascadeOnDelete();
        });

        $now = now();
        $generalCategoryIds = [];
        $fields = DB::table('registration_fields')->orderBy('id')->get();

        foreach ($fields as $field) {
            $categoryIds = DB::table('event_categories')
                ->where('event_id', $field->event_id)
                ->orderBy('id')
                ->pluck('id')
                ->all();

            if (empty($categoryIds)) {
                if (! array_key_exists($field->event_id, $generalCategoryIds)) {
                    $generalCategoryIds[$field->event_id] = DB::table('event_categories')->insertGetId([
                        'event_id' => $field->event_id,
                        'name' => 'General',
                        'description' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }

                $categoryIds = [$generalCategoryIds[$field->event_id]];
            }

            $primaryCategoryId = array_shift($categoryIds);

            DB::table('registration_fields')
                ->where('id', $field->id)
                ->update([
                    'event_category_id' => $primaryCategoryId,
                    'updated_at' => $now,
                ]);

            foreach ($categoryIds as $categoryId) {
                DB::table('registration_fields')->insert([
                    'event_id' => $field->event_id,
                    'event_category_id' => $categoryId,
                    'field_name' => $field->field_name,
                    'field_type' => $field->field_type,
                    'required' => $field->required,
                    'options' => $field->options,
                    'created_at' => $field->created_at ?? $now,
                    'updated_at' => $field->updated_at ?? $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('registration_fields', function (Blueprint $table) {
            $table->dropConstrainedForeignId('event_category_id');
        });
    }
};