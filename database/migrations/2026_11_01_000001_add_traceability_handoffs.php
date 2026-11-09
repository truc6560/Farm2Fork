<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tiep_nhan_nguyen_lieu', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('lo_nong_san_id')->unique();
            $t->foreign('lo_nong_san_id')->references('lo_nong_san_id')->on('lo_nong_san');
            foreach (['to_chuc_giao_id', 'to_chuc_nhan_id'] as $column) {
                $t->unsignedBigInteger($column);
                $t->foreign($column)->references('to_chuc_id')->on('to_chuc');
            }
            $t->unsignedBigInteger('nguoi_nhan_id');
            $t->foreign('nguoi_nhan_id')->references('tai_khoan_id')->on('tai_khoan_he_thong');
            $t->decimal('so_luong_giao', 14, 3);
            $t->decimal('so_luong_nhan', 14, 3);
            $t->string('don_vi_tinh', 30);
            $t->dateTime('thoi_gian_nhan');
            $t->text('ghi_chu')->nullable();
        });
        Schema::create('bien_dong_kho', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('lo_nong_san_id');
            $t->foreign('lo_nong_san_id')->references('lo_nong_san_id')->on('lo_nong_san');
            $t->unsignedBigInteger('sscc_id')->nullable();
            $t->foreign('sscc_id')->references('sscc_id')->on('don_vi_dong_goi_sscc');
            $t->unsignedBigInteger('kho_lanh_id');
            $t->foreign('kho_lanh_id')->references('kho_lanh_id')->on('kho_lanh');
            $t->unsignedBigInteger('tai_khoan_id');
            $t->foreign('tai_khoan_id')->references('tai_khoan_id')->on('tai_khoan_he_thong');
            $t->unsignedBigInteger('phieu_giao_hang_id')->nullable();
            $t->foreign('phieu_giao_hang_id')->references('phieu_giao_hang_id')->on('phieu_giao_hang');
            $t->string('loai', 10);
            $t->decimal('so_luong', 14, 3);
            $t->string('don_vi_tinh', 30);
            $t->dateTime('thoi_gian');
        });
        Schema::table('phieu_giao_hang', function (Blueprint $t) {
            $t->unsignedBigInteger('to_chuc_giao_id')->nullable();
            $t->foreign('to_chuc_giao_id')->references('to_chuc_id')->on('to_chuc');
        });
        Schema::create('so_luong_giao_nhan', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('chi_tiet_giao_nhan_id');
            $t->foreign('chi_tiet_giao_nhan_id')->references('chi_tiet_giao_nhan_id')->on('chi_tiet_giao_nhan');
            $t->unsignedBigInteger('lo_nong_san_id');
            $t->foreign('lo_nong_san_id')->references('lo_nong_san_id')->on('lo_nong_san');
            $t->decimal('so_luong_giao', 14, 3);
            $t->decimal('so_luong_nhan', 14, 3)->nullable();
            $t->string('don_vi_tinh', 30);
            $t->text('ly_do')->nullable();
            $t->unique(['chi_tiet_giao_nhan_id', 'lo_nong_san_id'], 'giao_nhan_lo_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('so_luong_giao_nhan');
        Schema::dropIfExists('bien_dong_kho');
        Schema::dropIfExists('tiep_nhan_nguyen_lieu');
        Schema::table('phieu_giao_hang', function (Blueprint $t) {
            $t->dropForeign(['to_chuc_giao_id']);
            $t->dropColumn('to_chuc_giao_id');
        });
    }
};
