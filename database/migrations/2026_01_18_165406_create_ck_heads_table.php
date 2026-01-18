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
        Schema::create('ck_heads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ck_set_id')->constrained('ck_sets')->onDelete('cascade');
            $table->string('baslik'); // Çalışma kağıdı başlığı
            $table->enum('ck_type', ['bilanco', 'serbest'])->default('bilanco');
            $table->foreignId('bilanco_row_id')->nullable()->constrained('bilanco_rows')->onDelete('set null');
            $table->text('full_path')->nullable(); // Sadece bilançoya bağlı CK'lerde dolu
            $table->integer('order_no')->default(0); // Sıralama numarası
            $table->timestamps();
            
            $table->index('ck_set_id');
            $table->index('bilanco_row_id');
            $table->index(['ck_set_id', 'order_no']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ck_heads');
    }
};
