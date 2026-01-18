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
        Schema::create('bilanco_rows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bilanco_import_id')->constrained('bilanco_imports')->onDelete('cascade');
            $table->string('account_name'); // Hesap adı (örn: "Kasa")
            $table->text('path'); // Hiyerarşik path (örn: "VARLIKLAR > DÖNEN VARLIKLAR > Kasa")
            $table->integer('level')->default(0); // Hiyerarşi seviyesi
            $table->decimal('cari_donem', 18, 2)->nullable(); // Bağımsız Denetimden Geçmiş Cari Dönem
            $table->decimal('onceki_donem', 18, 2)->nullable(); // Bağımsız Denetimden Geçmiş Önceki Dönem
            $table->decimal('acilis_bakiyeleri', 18, 2)->nullable(); // Bağımsız Denetimden Geçmiş Açılış Bakiyeleri
            $table->timestamps();
            
            $table->index('bilanco_import_id');
            $table->index('path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bilanco_rows');
    }
};
