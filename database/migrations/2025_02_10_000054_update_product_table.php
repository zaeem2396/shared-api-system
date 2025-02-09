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
        Schema::table('products', function (Blueprint $table) {
            $table->integer('subCategoryId')->after('categoryId')->nullable();
            $table->integer('vendorId')->change();
            $table->integer('categoryId')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('subCategoryId');
            $table->dropColumn('vendorId')->change();
            $table->dropColumn('categoryId')->change();
        });
    }
};
