<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('mua_vu') || !Schema::hasTable('lo_nong_san') || !Schema::hasTable('nhat_ky_canh_tac')) {
            return;
        }

        DB::table('mua_vu')
            ->where('trang_thai', 'DANG_CANH_TAC')
            ->where(function ($query) {
                $query->whereExists(function ($lots) {
                    $lots->selectRaw('1')->from('lo_nong_san')
                        ->whereColumn('lo_nong_san.mua_vu_id', 'mua_vu.mua_vu_id')
                        ->where('lo_nong_san.loai_lo', 'NGUYEN_LIEU');
                })->orWhereExists(function ($logs) {
                    $logs->selectRaw('1')->from('nhat_ky_canh_tac')
                        ->whereColumn('nhat_ky_canh_tac.mua_vu_id', 'mua_vu.mua_vu_id')
                        ->where('nhat_ky_canh_tac.loai_hoat_dong', 'THU_HOACH')
                        ->where('nhat_ky_canh_tac.trang_thai', 'DA_XAC_NHAN');
                });
            })->update(['trang_thai' => 'DANG_THU_HOACH']);
    }

    public function down(): void
    {
        // Không đảo trạng thái vì mùa vụ có thể tiếp tục được cập nhật sau khi đồng bộ.
    }
};
