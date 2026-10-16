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
        Schema::table('parser_products', function (Blueprint $table) {
            // Добавляем недостающие поля из описания модели
            $table->foreignId('brand_id')->nullable()->after('product_id')->constrained('brands')->nullOnDelete();
            $table->string('maker_id')->nullable()->after('brand_id');
            $table->json('data')->nullable()->after('maker_id');

            // Убираем поля, которых нет в описании модели
            $table->dropColumn([
                'slug',
                'short',
                'description',
                'packages',
                'colors',
                'packs',
                'fragile',
                'sanctioned',
                'composite',
                'stores',
                'materials',
                'care',
                'dimensions',
                'variants',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('parser_products', function (Blueprint $table) {
            $table->string('slug')->nullable();
            $table->string('short')->default('');
            $table->text('description')->nullable();
            $table->json('packages')->nullable();
            $table->json('colors')->nullable();
            $table->integer('packs')->default(1);
            $table->boolean('fragile')->default(false);
            $table->boolean('sanctioned')->default(false);
            $table->json('composite');
            $table->json('stores');
            $table->json('materials')->nullable();
            $table->text('care')->nullable();
            $table->json('dimensions')->nullable();
            $table->json('variants')->nullable();
        });

        Schema::table('parser_products', function (Blueprint $table) {
            $table->dropConstrainedForeignId('brand_id');
            $table->dropColumn(['maker_id', 'data']);
        });
    }
};
