<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('lich_su_trang_thai_mua_vu')) {
            return;
        }

        Schema::create('lich_su_trang_thai_mua_vu', function (Blueprint $table) {
            $table->bigIncrements('lich_su_id');
            $table->unsignedBigInteger('mua_vu_id')->index();
            $table->unsignedBigInteger('tai_khoan_id')->nullable()->index();
            $table->string('trang_thai_cu', 40)->nullable();
            $table->string('trang_thai_moi', 40);
            $table->text('ly_do');
            $table->timestamp('thoi_gian');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lich_su_trang_thai_mua_vu');
    }
};
