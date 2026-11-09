<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('nhan_vien_vung_trong', function (Blueprint $table) {
            $table->foreignId('tai_khoan_id')->constrained('tai_khoan_he_thong', 'tai_khoan_id')->cascadeOnDelete();
            $table->foreignId('vung_trong_id')->constrained('vung_trong', 'vung_trong_id')->cascadeOnDelete();
            $table->date('ngay_phan_cong')->default(DB::raw('(CURRENT_DATE)'));
            $table->string('ghi_chu', 255)->nullable();
            $table->timestamps();

            $table->primary(['tai_khoan_id', 'vung_trong_id']);
        });

        // 1. Backfill một lần: chép dữ liệu nguoi_phu_trach_id hiện có sang nhan_vien_vung_trong
        $existing = DB::table('vung_trong')
            ->whereNotNull('nguoi_phu_trach_id')
            ->select('nguoi_phu_trach_id', 'vung_trong_id', 'created_at')
            ->get();

        foreach ($existing as $item) {
            $userExists = DB::table('tai_khoan_he_thong')
                ->where('tai_khoan_id', $item->nguoi_phu_trach_id)
                ->exists();

            if ($userExists) {
                DB::table('nhan_vien_vung_trong')->insertOrIgnore([
                    'tai_khoan_id' => $item->nguoi_phu_trach_id,
                    'vung_trong_id' => $item->vung_trong_id,
                    'ngay_phan_cong' => $item->created_at ? substr((string)$item->created_at, 0, 10) : now()->toDateString(),
                    'ghi_chu' => 'Backfill từ người phụ trách ban đầu',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nhan_vien_vung_trong');
    }
};

