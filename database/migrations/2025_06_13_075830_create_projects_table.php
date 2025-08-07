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
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description');
            $table->enum('status', ['Ongoing', 'Prospect', 'Complete', 'Cancel'])->default('Ongoing');
            $table->date('start_date');
            $table->date('finish_date')->nullable();
            $table->foreignId('mitra_id')->nullable()->constrained('partners')->nullOnDelete();
            $table->enum('kategori', [
                'PLTS Hybrid', 
                'PLTS Ongrid', 
                'PLTS Offgrid', 
                'PJUTS All In One', 
                'PJUTS Two In One', 
                'PJUTS Konvensional', 
            ])->default('PLTS Hybrid');
            $table->text('lokasi')->nullable();
            $table->text('no_po')->nullable();
            $table->text('no_so')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
