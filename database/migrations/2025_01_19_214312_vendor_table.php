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
        Schema::create('vendors', function (Blueprint $tabel) {
            $tabel->id();
            $tabel->string('userId');
            $tabel->string('storeName')->nullable();
            $tabel->string('storeDescription');
            $tabel->string('logo')->nullable();
            $tabel->string('status');
            $tabel->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendors');
    }
};
