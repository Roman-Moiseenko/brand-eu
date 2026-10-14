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
        // Переименовываем уникальный код парсинга ikea_id -> code
        Schema::table('parser_categories', function (Blueprint $table) {
            $table->dropUnique(['ikea_id']);
        });

        Schema::table('parser_categories', function (Blueprint $table) {
            $table->renameColumn('ikea_id', 'code');
        });

        Schema::table('parser_categories', function (Blueprint $table) {
            $table->unique('code');
        });

        // Связанная категория каталога (Catalog\Category::id)
        Schema::table('parser_categories', function (Blueprint $table) {
            $table->foreignId('category_id')
                ->nullable()
                ->after('brand_id')
                ->constrained('categories')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('parser_categories', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
        });

        Schema::table('parser_categories', function (Blueprint $table) {
            $table->dropColumn('category_id');
        });

        Schema::table('parser_categories', function (Blueprint $table) {
            $table->dropUnique(['code']);
        });

        Schema::table('parser_categories', function (Blueprint $table) {
            $table->renameColumn('code', 'ikea_id');
        });

        Schema::table('parser_categories', function (Blueprint $table) {
            $table->unique('ikea_id');
        });
    }
};
