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
        Schema::create('parser_product_sizes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('parser_products')->cascadeOnDelete();
            $table->string('size_original')->nullable();
            $table->string('size')->nullable();
            $table->foreignId('product_size_id')->nullable()->constrained('products_sizes')->nullOnDelete();
            $table->boolean('available')->default(true);
            $table->string('url')->nullable();
            $table->string('sku')->nullable();
            $table->float('price')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('parser_product_sizes');
    }
};
