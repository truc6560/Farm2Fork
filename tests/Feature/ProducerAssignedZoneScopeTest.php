<?php

namespace Tests\Feature;

use App\Models\DanhMucSanPham;
use App\Models\LoNongSan;
use App\Models\MuaVu;
use App\Models\NhatKyCanhTac;
use App\Models\TaiKhoanHeThong;
use App\Models\VaiTro;
use App\Models\VungTrong;
use App\Services\ProducerAuthorizationService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProducerAssignedZoneScopeTest extends TestCase
{
    protected TaiKhoanHeThong $producerA;
    protected TaiKhoanHeThong $producerB;
    protected VungTrong $zoneA;
    protected VungTrong $zoneB;
    protected MuaVu $seasonA;
    protected MuaVu $seasonB;
    protected NhatKyCanhTac $logA;
    protected NhatKyCanhTac $logB;
    protected LoNongSan $lotA;
    protected LoNongSan $lotB;

    protected function setUp(): void
    {
        parent::setUp();

        $role = VaiTro::where('ma_vai_tro', 'NGUOI_SAN_XUAT')->firstOrFail();

        // 1. Tạo 2 tài khoản Producer A và B
        $this->producerA = TaiKhoanHeThong::create([
            'vai_tro_id' => $role->vai_tro_id,
            'to_chuc_id' => 1,
            'ho_ten' => 'Producer A Test',
            'email' => 'producer_a_' . uniqid() . '@example.com',
            'mat_khau_hash' => Hash::make('Secret@2026!'),
            'trang_thai' => 'DANG_HOAT_DONG',
        ]);

        $this->producerB = TaiKhoanHeThong::create([
            'vai_tro_id' => $role->vai_tro_id,
            'to_chuc_id' => 1,
            'ho_ten' => 'Producer B Test',
            'email' => 'producer_b_' . uniqid() . '@example.com',
            'mat_khau_hash' => Hash::make('Secret@2026!'),
            'trang_thai' => 'DANG_HOAT_DONG',
        ]);

        // 2. Tạo 2 vùng trồng A và B
        $this->zoneA = VungTrong::create([
            'to_chuc_id' => 1,
            'ten_vung_trong' => 'Vùng trồng A Test ' . uniqid(),
            'dia_chi' => 'Địa chỉ A',
            'cay_trong_chinh' => 'Dâu tây',
            'dien_tich_ha' => 2.5,
            'trang_thai' => 'DANG_SU_DUNG',
        ]);

        $this->zoneB = VungTrong::create([
            'to_chuc_id' => 1,
            'ten_vung_trong' => 'Vùng trồng B Test ' . uniqid(),
            'dia_chi' => 'Địa chỉ B',
            'cay_trong_chinh' => 'Xoài cát',
            'dien_tich_ha' => 3.0,
            'trang_thai' => 'DANG_SU_DUNG',
        ]);

        // Phân công vùng A cho A, vùng B cho B qua bảng trung gian
        ProducerAuthorizationService::assignZoneToUser($this->producerA->tai_khoan_id, $this->zoneA->vung_trong_id, 'Phân công test A');
        ProducerAuthorizationService::assignZoneToUser($this->producerB->tai_khoan_id, $this->zoneB->vung_trong_id, 'Phân công test B');

        // 3. Tạo mùa vụ A và B
        $product = DanhMucSanPham::first() ?? DanhMucSanPham::create([
            'ma_san_pham' => 'SP-TEST',
            'ten_san_pham' => 'Sản phẩm Test',
            'trang_thai' => 'DANG_SU_DUNG',
        ]);

        $this->seasonA = MuaVu::create([
            'vung_trong_id' => $this->zoneA->vung_trong_id,
            'san_pham_id' => $product->san_pham_id,
            'ma_mua_vu' => 'MV-A-' . uniqid(),
            'ten_mua_vu' => 'Mùa vụ A',
            'don_vi_san_luong' => 'kg',
            'trang_thai' => 'DANG_CANH_TAC',
        ]);

        $this->seasonB = MuaVu::create([
            'vung_trong_id' => $this->zoneB->vung_trong_id,
            'san_pham_id' => $product->san_pham_id,
            'ma_mua_vu' => 'MV-B-' . uniqid(),
            'ten_mua_vu' => 'Mùa vụ B',
            'don_vi_san_luong' => 'kg',
            'trang_thai' => 'DANG_CANH_TAC',
        ]);

        // 4. Tạo nhật ký canh tác A và B
        $this->logA = NhatKyCanhTac::create([
            'mua_vu_id' => $this->seasonA->mua_vu_id,
            'tai_khoan_id' => $this->producerA->tai_khoan_id,
            'loai_hoat_dong' => 'TUOI_NUOC',
            'ngay_thuc_hien' => now(),
            'tep_minh_chung' => 'test_a.jpg',
            'trang_thai' => 'DA_XAC_NHAN',
        ]);

        $this->logB = NhatKyCanhTac::create([
            'mua_vu_id' => $this->seasonB->mua_vu_id,
            'tai_khoan_id' => $this->producerB->tai_khoan_id,
            'loai_hoat_dong' => 'TUOI_NUOC',
            'ngay_thuc_hien' => now(),
            'tep_minh_chung' => 'test_b.jpg',
            'trang_thai' => 'DA_XAC_NHAN',
        ]);

        // 5. Tạo lô thu hoạch A và B
        $this->lotA = LoNongSan::create([
            'san_pham_id' => $product->san_pham_id,
            'mua_vu_id' => $this->seasonA->mua_vu_id,
            'to_chuc_nguon_id' => 1,
            'loai_lo' => 'NGUYEN_LIEU',
            'ma_lo' => 'LO-A-' . uniqid(),
            'so_luong' => 100,
            'don_vi_tinh' => 'kg',
            'ngay_thu_hoach' => now()->toDateString(),
            'trang_thai' => 'MOI_TAO',
        ]);

        $this->lotB = LoNongSan::create([
            'san_pham_id' => $product->san_pham_id,
            'mua_vu_id' => $this->seasonB->mua_vu_id,
            'to_chuc_nguon_id' => 1,
            'loai_lo' => 'NGUYEN_LIEU',
            'ma_lo' => 'LO-B-' . uniqid(),
            'so_luong' => 150,
            'don_vi_tinh' => 'kg',
            'ngay_thu_hoach' => now()->toDateString(),
            'trang_thai' => 'MOI_TAO',
        ]);
    }

    protected function tearDown(): void
    {
        // Cleanup test data
        $ids = [$this->producerA->tai_khoan_id, $this->producerB->tai_khoan_id];
        DB::table('nhan_vien_vung_trong')->whereIn('tai_khoan_id', $ids)->delete();
        $this->logA->delete();
        $this->logB->delete();
        $this->lotA->delete();
        $this->lotB->delete();
        $this->seasonA->delete();
        $this->seasonB->delete();
        $this->zoneA->delete();
        $this->zoneB->delete();
        TaiKhoanHeThong::whereIn('tai_khoan_id', $ids)->delete();

        parent::tearDown();
    }

    public function test_producer_a_can_view_own_zone_but_denied_producer_b_zone(): void
    {
        // Producer A xem vùng A -> 200
        $resA = $this->actingAs($this->producerA)->get("/producer/planting-zones/{$this->zoneA->vung_trong_id}");
        $resA->assertStatus(200);

        // Producer A xem vùng B của B -> 403 Forbidden
        $resB = $this->actingAs($this->producerA)->get("/producer/planting-zones/{$this->zoneB->vung_trong_id}");
        $resB->assertStatus(403);

        // Producer A sửa vùng B của B -> 403 Forbidden
        $resEdit = $this->actingAs($this->producerA)->get("/producer/planting-zones/{$this->zoneB->vung_trong_id}/edit");
        $resEdit->assertStatus(403);

        // Producer A cập nhật vùng B -> 403 Forbidden
        $resUpdate = $this->actingAs($this->producerA)->put("/producer/planting-zones/{$this->zoneB->vung_trong_id}", [
            'cay_trong_chinh' => 'Sầu riêng',
            'dien_tich_ha' => 5,
            'dia_chi' => 'Đổi trộm địa chỉ',
            'trang_thai' => 'DANG_SU_DUNG',
        ]);
        $resUpdate->assertStatus(403);
    }

    public function test_producer_a_denied_producer_b_season(): void
    {
        // Xem mùa vụ của mình -> 200
        $resOwn = $this->actingAs($this->producerA)->get("/producer/seasons/{$this->seasonA->mua_vu_id}");
        $resOwn->assertStatus(200);

        // Xem mùa vụ của B -> 403
        $resOther = $this->actingAs($this->producerA)->get("/producer/seasons/{$this->seasonB->mua_vu_id}");
        $resOther->assertStatus(403);

        // Tạo mùa vụ gán vào vùng B -> 403
        $resCreate = $this->actingAs($this->producerA)->post('/producer/seasons', [
            'vung_trong_id' => $this->zoneB->vung_trong_id,
            'ten_san_pham' => 'Nông sản test',
            'don_vi_san_luong' => 'kg',
            'trang_thai' => 'DANG_CANH_TAC',
        ]);
        $resCreate->assertStatus(403);
    }

    public function test_producer_a_denied_producer_b_farming_log(): void
    {
        // Xem nhật ký của mình -> 200
        $resOwn = $this->actingAs($this->producerA)->get("/producer/farming-logs/{$this->logA->nhat_ky_id}");
        $resOwn->assertStatus(200);

        // Xem nhật ký của B -> 403
        $resOther = $this->actingAs($this->producerA)->get("/producer/farming-logs/{$this->logB->nhat_ky_id}");
        $resOther->assertStatus(403);

        // Sửa nhật ký của B -> 403
        $resEdit = $this->actingAs($this->producerA)->get("/producer/farming-logs/{$this->logB->nhat_ky_id}/edit");
        $resEdit->assertStatus(403);
    }

    public function test_producer_a_denied_producer_b_harvest_lot(): void
    {
        // Xem lô của mình -> 200
        $resOwn = $this->actingAs($this->producerA)->get("/producer/lots/{$this->lotA->lo_nong_san_id}");
        $resOwn->assertStatus(200);

        // Xem lô của B -> 403
        $resOther = $this->actingAs($this->producerA)->get("/producer/lots/{$this->lotB->lo_nong_san_id}");
        $resOther->assertStatus(403);
    }

    public function test_producer_creating_new_zone_automatically_assigned(): void
    {
        Storage::fake('public');

        $response = $this->actingAs($this->producerA)->post('/producer/planting-zones', [
            'cay_trong_chinh' => 'Bơ Booth',
            'dien_tich_ha' => 4.2,
            'dia_chi' => 'Đăk Lăk',
            'tep_chung_nhan' => UploadedFile::fake()->create('chungnhan.pdf', 500, 'application/pdf'),
            'trang_thai' => 'DANG_SU_DUNG',
        ]);

        $newZone = VungTrong::where('cay_trong_chinh', 'Bơ Booth')->latest('vung_trong_id')->first();
        $this->assertNotNull($newZone);
        $response->assertRedirect("/producer/planting-zones/{$newZone->vung_trong_id}");

        // Kiểm tra đã tự động có trong bảng trung gian nhan_vien_vung_trong
        $assigned = DB::table('nhan_vien_vung_trong')
            ->where('tai_khoan_id', $this->producerA->tai_khoan_id)
            ->where('vung_trong_id', $newZone->vung_trong_id)
            ->exists();
        $this->assertTrue($assigned);

        // Cleanup
        DB::table('nhan_vien_vung_trong')->where('vung_trong_id', $newZone->vung_trong_id)->delete();
        $newZone->delete();
    }
}

