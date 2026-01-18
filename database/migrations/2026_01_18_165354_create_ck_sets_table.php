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
        Schema::create('ck_sets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->date('donem_tarihi'); // Dönem tarihi
            $table->foreignId('bilanco_import_id')->nullable()->constrained('bilanco_imports')->onDelete('set null');
            $table->enum('status', ['draft', 'completed'])->default('draft');
            $table->timestamps();
            
            $table->index(['company_id', 'donem_tarihi']);
            $table->index('bilanco_import_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ck_sets');
    }
};
