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
        Schema::table('parser_categories', function (Blueprint $table) {
            $table->foreignId('size_grid_id')
                ->nullable()
                ->after('category_id')
                ->constrained('size_grids')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('parser_categories', function (Blueprint $table) {
            $table->dropConstrainedForeignId('size_grid_id');
        });
    }
};
