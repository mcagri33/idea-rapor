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
        Schema::create('ck_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ck_head_id')->constrained('ck_heads')->onDelete('cascade');
            $table->string('satir_adi'); // Satır adı
            $table->decimal('cari', 18, 2)->nullable(); // Cari dönem
            $table->decimal('onceki', 18, 2)->nullable(); // Önceki dönem
            $table->decimal('acilis', 18, 2)->nullable(); // Açılış bakiyeleri
            $table->decimal('fark', 18, 2)->nullable(); // Fark (cari - onceki)
            $table->timestamps();
            
            $table->index('ck_head_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ck_lines');
    }
};
