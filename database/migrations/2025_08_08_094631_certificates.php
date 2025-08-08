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
        Schema::create('certificates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('no_certificate', 30);
            $table->foreignId('project_id')->nullable()->constrained('projects')->onDelete('set null');
            $table->foreignId('barang_certificate_id')->nullable()->constrained('barang_certificates')->onDelete('set null');
            $table->enum('status', ['Belum', 'Tidak Aktif', 'Aktif'])->default('Belum');
            $table->date('date_of_issue');
            $table->date('date_of_expired');
            $table->string('attachment', 255)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('certificates');
    }
};
