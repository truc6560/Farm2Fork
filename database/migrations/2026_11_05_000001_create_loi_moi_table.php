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
        Schema::create('loi_moi', function (Blueprint $table) {
            $table->id();
            $table->string('email', 191)->index();
            $table->foreignId('vai_tro_id')->constrained('vai_tro', 'vai_tro_id');
            $table->foreignId('to_chuc_id')->nullable()->constrained('to_chuc', 'to_chuc_id')->nullOnDelete();
            $table->foreignId('kho_lanh_id')->nullable()->constrained('kho_lanh', 'kho_lanh_id')->nullOnDelete();
            $table->foreignId('phuong_tien_id')->nullable()->constrained('phuong_tien', 'phuong_tien_id')->nullOnDelete();
            $table->foreignId('vung_trong_id')->nullable()->constrained('vung_trong', 'vung_trong_id')->nullOnDelete();
            $table->string('ma_hash', 64)->unique();
            $table->dateTime('het_han');
            $table->string('trang_thai', 30)->default('cho');
            $table->foreignId('nguoi_moi_id')->constrained('tai_khoan_he_thong', 'tai_khoan_id');
            $table->timestamps();

            $table->index(['email', 'trang_thai']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loi_moi');
    }
};

