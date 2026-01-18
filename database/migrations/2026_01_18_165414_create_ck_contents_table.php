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
        Schema::create('ck_contents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ck_head_id')->constrained('ck_heads')->onDelete('cascade');
            $table->enum('section', ['aciklama', 'denetim_proseduru', 'bulgular', 'sonuc'])->default('aciklama');
            $table->longText('content')->nullable(); // Rich text içerik (denetçi müdahalesi için)
            $table->timestamps();
            
            $table->index('ck_head_id');
            $table->unique(['ck_head_id', 'section']); // Her section için bir kayıt
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ck_contents');
    }
};
