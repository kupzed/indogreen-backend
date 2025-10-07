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
        Schema::create('certificate_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('certificate_id')->constrained()->onDelete('cascade');
            $table->string('name', 60);
            $table->string('description', 255);
            $table->string('file_path', 255);
            $table->string('mime', 191)->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('certificate_attachments');
    }
};
