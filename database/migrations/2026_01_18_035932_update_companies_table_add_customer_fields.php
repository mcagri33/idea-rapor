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
        // Önce mevcut verileri temizle (eski format ile uyumsuz olduğu için)
        \Illuminate\Support\Facades\DB::table('companies')->truncate();
        
        Schema::table('companies', function (Blueprint $table) {
            // Eski kolonları kaldır
            $table->dropColumn(['idea_id', 'unvan', 'vergi_no']);
            
            // Yeni kolonları ekle (CustomerSyncService formatına göre)
            // Önce unique olmadan ekle, sonra unique yap
            $table->string('external_id')->nullable()->after('id');
            $table->string('name')->after('external_id');
            $table->string('company')->nullable()->after('name');
            $table->string('email')->nullable()->after('company');
            $table->boolean('is_active')->default(true)->after('email');
            $table->timestamp('synced_at')->nullable()->after('is_active');
        });
        
        // Şimdi unique constraint ekle
        Schema::table('companies', function (Blueprint $table) {
            $table->unique('external_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            // Yeni kolonları kaldır
            $table->dropColumn(['external_id', 'name', 'company', 'email', 'is_active', 'synced_at']);
            
            // Eski kolonları geri ekle
            $table->string('idea_id')->unique()->after('id');
            $table->string('unvan')->after('idea_id');
            $table->string('vergi_no')->nullable()->after('unvan');
        });
    }
};
