<?php
namespace Tests\Feature;

use App\Models\{TaiKhoanHeThong, LoNongSan};
use App\Services\TraceabilityHandoffService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class TraceabilityHandoffTest extends TestCase
{
    private TraceabilityHandoffService $service;
    private TaiKhoanHeThong $user;

    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'handoff_test', 'database.connections.handoff_test' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true]]);
        foreach (json_decode(file_get_contents(__DIR__.'/../Fixtures/trace_schema.json'), true) as $name => $columns) {
            Schema::create($name, function (Blueprint $table) use ($columns) {
                foreach ($columns as $column) {
                    if ($column['auto_increment']) { $table->bigIncrements($column['name']); continue; }
                    $field = str_contains($column['type'], 'int') ? $table->unsignedBigInteger($column['name']) : (str_contains($column['type'], 'decimal') ? $table->decimal($column['name'], 14, 3) : $table->text($column['name']));
                    $field->nullable();
                }
            });
        }
        (require database_path('migrations/2026_11_01_000001_add_traceability_handoffs.php'))->up();
        Schema::table('bien_dong_kho', function (Blueprint $table) {
            $table->decimal('nhiet_do_luc_xuat', 6, 2)->nullable();
        });
        if (!Schema::hasTable('phieu_kiem_dinh')) {
            Schema::create('phieu_kiem_dinh', function (Blueprint $table) {
                $table->bigIncrements('phieu_id');
                $table->unsignedBigInteger('lo_nong_san_id');
                $table->string('loai_kiem_dinh', 40);
                $table->string('ket_luan', 40);
                $table->dateTime('ngay_kiem_dinh')->nullable();
            });
        }
        if (!Schema::hasTable('lich_su_trang_thai_lo')) {
            Schema::create('lich_su_trang_thai_lo', function (Blueprint $table) {
                $table->bigIncrements('lich_su_id');
                $table->unsignedBigInteger('lo_nong_san_id');
                $table->unsignedBigInteger('tai_khoan_id')->nullable();
                $table->string('trang_thai_cu', 40)->nullable();
                $table->string('trang_thai_moi', 40);
                $table->text('ly_do')->nullable();
                $table->dateTime('thoi_gian');
            });
        }
        foreach ([1,2,3] as $id) DB::table('to_chuc')->insert(['to_chuc_id' => $id, 'ten_to_chuc' => 'Organization '.$id, 'trang_thai' => 'DANG_HOAT_DONG']);
        DB::table('tai_khoan_he_thong')->insert(['tai_khoan_id' => 1, 'to_chuc_id' => 2]);
        DB::table('kho_lanh')->insert(['kho_lanh_id' => 1]);
        DB::table('nhan_vien_kho')->insert(['kho_lanh_id' => 1, 'tai_khoan_id' => 1]);
        DB::table('chuyen_van_chuyen')->insert(['chuyen_id' => 1, 'tai_xe_id' => 1, 'trang_thai' => 'CHUAN_BI']);
        DB::table('lo_nong_san')->insert(['lo_nong_san_id' => 1, 'loai_lo' => 'NGUYEN_LIEU', 'trang_thai' => 'MOI_TAO', 'so_luong' => 100, 'don_vi_tinh' => 'kg', 'to_chuc_nguon_id' => 1]);
        DB::table('lo_nong_san')->insert(['lo_nong_san_id' => 2, 'loai_lo' => 'THANH_PHAM', 'trang_thai' => 'DAT_KIEM_DINH', 'so_luong' => 10, 'don_vi_tinh' => 'kg', 'to_chuc_nguon_id' => 1]);
        DB::table('phieu_kiem_dinh')->insert(['phieu_id' => 1, 'lo_nong_san_id' => 2, 'loai_kiem_dinh' => 'TRUOC_XUAT_XUONG', 'ket_luan' => 'DAT', 'ngay_kiem_dinh' => now()]);
        DB::table('don_vi_dong_goi_sscc')->insert(['sscc_id' => 1, 'ma_sscc' => '089385002781441655', 'kho_lanh_id' => 1, 'trang_thai' => 'TRONG_KHO']);
        DB::table('chi_tiet_dong_goi_sscc')->insert(['sscc_id' => 1, 'lo_nong_san_id' => 2, 'so_luong' => 10, 'don_vi_tinh' => 'kg']);
        $this->user = TaiKhoanHeThong::findOrFail(1);
        $this->service = app(TraceabilityHandoffService::class);
    }

    private function dispatch(): int
    {
        return $this->service->dispatch(1, ['chuyen_id' => 1, 'to_chuc_nhan_id' => 3, 'dia_chi_giao' => 'Store A', 'nhiet_do_luc_xuat' => 2.5], $this->user);
    }

    private function startTrip(): void
    {
        DB::table('chuyen_van_chuyen')->where('chuyen_id', 1)->update(['trang_thai' => 'DANG_VAN_CHUYEN']);
        DB::table('phieu_giao_hang')->where('chuyen_id', 1)->update(['trang_thai' => 'DANG_GIAO']);
    }

    public function test_receipt_preserves_original_quantity_and_rejects_duplicate(): void
    {
        $data = ['to_chuc_giao_id' => 1, 'so_luong_thuc_nhan' => 98, 'ghi_chu' => '2 kg rejected'];
        $this->service->receiveRaw(1, $data, $this->user);
        $this->assertDatabaseHas('tiep_nhan_nguyen_lieu', ['so_luong_giao' => 100, 'so_luong_nhan' => 98, 'to_chuc_nhan_id' => 2]);
        $this->assertDatabaseHas('lo_nong_san', ['so_luong' => 98, 'trang_thai' => 'CHO_KIEM_DINH']);
        $this->expectException(ValidationException::class);
        $this->service->receiveRaw(1, $data, $this->user);
    }

    public function test_dispatch_links_sender_recipient_trip_and_quantity_and_cannot_repeat(): void
    {
        $id = $this->dispatch();
        $this->assertSame(3, (int)\App\Models\PhieuGiaoHang::with('toChucNhan')->findOrFail($id)->toChucNhan->to_chuc_id);
        $this->assertDatabaseHas('phieu_giao_hang', ['phieu_giao_hang_id' => $id, 'to_chuc_giao_id' => 2, 'to_chuc_nhan_id' => 3, 'chuyen_id' => 1]);
        $this->assertDatabaseHas('so_luong_giao_nhan', ['so_luong_giao' => 10, 'lo_nong_san_id' => 2]);
        $this->assertDatabaseHas('bien_dong_kho', ['loai' => 'XUAT', 'phieu_giao_hang_id' => $id, 'nhiet_do_luc_xuat' => 2.5]);
        $this->expectException(ValidationException::class);
        $this->dispatch();
    }

    public function test_wrong_warehouse_cannot_dispatch(): void
    {
        DB::table('nhan_vien_kho')->delete();
        try { $this->dispatch(); $this->fail('Expected rejection'); } catch (ValidationException $e) {}
        $this->assertDatabaseCount('phieu_giao_hang', 0);
        $this->assertDatabaseHas('don_vi_dong_goi_sscc', ['trang_thai' => 'TRONG_KHO']);
    }

    public function test_package_cannot_dispatch_before_outbound_qc_passes(): void
    {
        DB::table('lo_nong_san')->where('lo_nong_san_id', 2)->update(['trang_thai' => 'CHO_KIEM_DINH_XUAT_XUONG']);

        $this->expectException(ValidationException::class);
        $this->dispatch();
    }

    public function test_partial_delivery_is_saved_and_cannot_be_overwritten(): void
    {
        $id = $this->dispatch();
        $this->startTrip();
        $quantityId = DB::table('so_luong_giao_nhan')->value('id');
        $data = ['ten_nguoi_nhan_thuc_te' => 'Receiver', 'tinh_trang_bao_goi' => 'HU_HONG_MOT_PHAN', 'quantities' => [$quantityId => ['received' => 8, 'reason' => '2 kg damaged']]];
        $this->service->confirm($id, $data, $this->user, null);
        $this->assertDatabaseHas('so_luong_giao_nhan', ['so_luong_nhan' => 8, 'so_luong_giao' => 10]);
        $this->assertDatabaseHas('phieu_giao_hang', ['trang_thai' => 'GIAO_MOT_PHAN']);
        $this->expectException(ValidationException::class);
        $this->service->confirm($id, $data, $this->user, null);
    }

    public function test_extra_quantity_from_another_delivery_rolls_back_confirmation(): void
    {
        $id = $this->dispatch();
        $this->startTrip();
        $quantityId = DB::table('so_luong_giao_nhan')->value('id');
        try {
            $this->service->confirm($id, ['ten_nguoi_nhan_thuc_te' => 'Receiver', 'tinh_trang_bao_goi' => 'NGUYEN_VEN', 'quantities' => [$quantityId => ['received' => 10], 999 => ['received' => 1]]], $this->user, null);
            $this->fail('Expected rejection');
        } catch (ValidationException $e) {}
        $this->assertDatabaseHas('so_luong_giao_nhan', ['id' => $quantityId, 'so_luong_nhan' => null]);
        $this->assertDatabaseHas('phieu_giao_hang', ['trang_thai' => 'DANG_GIAO']);
    }

    public function test_full_delivery_and_refusal_have_distinct_outcomes(): void
    {
        $id = $this->dispatch();
        $q = DB::table('so_luong_giao_nhan')->value('id');
        DB::table('don_vi_dong_goi_sscc')->insert(['sscc_id' => 2, 'ma_sscc' => '189385002781441652', 'kho_lanh_id' => 1, 'trang_thai' => 'TRONG_KHO']);
        DB::table('chi_tiet_dong_goi_sscc')->insert(['sscc_id' => 2, 'lo_nong_san_id' => 2, 'so_luong' => 5, 'don_vi_tinh' => 'kg']);
        $id2 = $this->service->dispatch(2, ['chuyen_id' => 1, 'to_chuc_nhan_id' => 3, 'dia_chi_giao' => 'Store B', 'nhiet_do_luc_xuat' => 2.5], $this->user);
        $this->startTrip();
        $this->service->confirm($id, ['ten_nguoi_nhan_thuc_te' => 'Receiver', 'tinh_trang_bao_goi' => 'NGUYEN_VEN', 'quantities' => [$q => ['received' => 0, 'reason' => 'Refused']]], $this->user, null);
        $this->assertDatabaseHas('phieu_giao_hang', ['phieu_giao_hang_id' => $id, 'trang_thai' => 'TRA_VE']);
        $this->assertDatabaseHas('so_luong_giao_nhan', ['so_luong_giao' => 10, 'so_luong_nhan' => 0]);
        $q2 = DB::table('so_luong_giao_nhan')->max('id');
        $this->service->confirm($id2, ['ten_nguoi_nhan_thuc_te' => 'Receiver', 'tinh_trang_bao_goi' => 'NGUYEN_VEN', 'quantities' => [$q2 => ['received' => 5]]], $this->user, null);
        $this->assertDatabaseHas('phieu_giao_hang', ['phieu_giao_hang_id' => $id2, 'trang_thai' => 'DA_GIAO']);
    }

    public function test_over_receipt_and_missing_discrepancy_reason_are_rejected(): void
    {
        $id = $this->dispatch();
        $this->startTrip();
        $q = DB::table('so_luong_giao_nhan')->value('id');
        foreach ([11, 8] as $amount) {
            try {
                $this->service->confirm($id, ['ten_nguoi_nhan_thuc_te' => 'Receiver', 'tinh_trang_bao_goi' => 'NGUYEN_VEN', 'quantities' => [$q => ['received' => $amount]]], $this->user, null);
                $this->fail('Invalid quantity accepted');
            } catch (ValidationException $e) {}
        }
        $this->assertDatabaseHas('so_luong_giao_nhan', ['so_luong_nhan' => null]);
        $this->assertDatabaseCount('su_kien_hanh_trinh', 0);
    }
}
