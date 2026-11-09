<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bien_dong_kho', function (Blueprint $table) {
            $table->decimal('nhiet_do_luc_nhan', 6, 2)->nullable()->after('don_vi_tinh');
            $table->string('tinh_trang_bao_goi', 40)->nullable()->after('nhiet_do_luc_nhan');
            $table->string('vi_tri_luu_kho', 120)->nullable()->after('tinh_trang_bao_goi');
            $table->text('ghi_chu')->nullable()->after('vi_tri_luu_kho');
        });

        Schema::table('canh_bao_chuoi_lanh', function (Blueprint $table) {
            $table->string('danh_gia_lo', 30)->nullable()->after('ket_qua_khac_phuc');
            $table->text('ghi_chu_danh_gia')->nullable()->after('danh_gia_lo');
        });

        Schema::create('canh_bao_sscc', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('canh_bao_id');
            $table->unsignedBigInteger('sscc_id');
            $table->string('ket_qua_danh_gia', 30)->nullable();
            $table->text('ghi_chu')->nullable();
            $table->unique(['canh_bao_id', 'sscc_id']);
            $table->foreign('canh_bao_id')->references('canh_bao_id')->on('canh_bao_chuoi_lanh')->cascadeOnDelete();
            $table->foreign('sscc_id')->references('sscc_id')->on('don_vi_dong_goi_sscc');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('canh_bao_sscc');

        Schema::table('canh_bao_chuoi_lanh', function (Blueprint $table) {
            $table->dropColumn(['danh_gia_lo', 'ghi_chu_danh_gia']);
        });

        Schema::table('bien_dong_kho', function (Blueprint $table) {
            $table->dropColumn(['nhiet_do_luc_nhan', 'tinh_trang_bao_goi', 'vi_tri_luu_kho', 'ghi_chu']);
        });
    }
};
