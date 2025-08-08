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
        Schema::create('barang_certificates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('no_seri', 30);
            $table->foreignId('mitra_id')->nullable()->constrained('partners')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('barang_certificates');
    }
};
