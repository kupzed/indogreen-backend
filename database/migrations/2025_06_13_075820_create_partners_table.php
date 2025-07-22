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
        Schema::create('partners', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->boolean('is_pribadi')->default(false);
            $table->boolean('is_perusahaan')->default(false);
            $table->boolean('is_customer')->default(false);
            $table->boolean('is_vendor')->default(false);
            $table->text('alamat');
            $table->string('website')->nullable();
            $table->string('email');
            $table->string('kontak_1');
            $table->string('kontak_1_nama');
            $table->string('kontak_1_jabatan');
            $table->string('kontak_2_nama')->nullable();
            $table->string('kontak_2')->nullable();
            $table->string('kontak_2_jabatan')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('partners');
    }
}; 