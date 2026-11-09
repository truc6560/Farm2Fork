<?php

namespace Tests\Feature;

use App\Models\DanhMucSanPham;
use App\Models\DanhMucVatTu;
use App\Models\MuaVu;
use App\Models\NhatKyCanhTac;
use App\Models\TaiKhoanHeThong;
use App\Models\ToChuc;
use App\Models\VaiTro;
use App\Models\VungTrong;
use Tests\TestCase;

class ProducerModuleTest extends TestCase
{
    use \Illuminate\Foundation\Testing\DatabaseTransactions;

    private function assignedZone(TaiKhoanHeThong $user): VungTrong
    {
        $zone = VungTrong::create([
            'to_chuc_id' => $user->to_chuc_id,
            'ten_vung_trong' => 'Vùng kiểm thử ' . uniqid(),
            'dia_chi' => 'Lâm Đồng', 'dien_tich_ha' => 1,
            'trang_thai' => 'DANG_SU_DUNG',
        ]);
        \App\Services\ProducerAuthorizationService::assignZoneToUser($user->tai_khoan_id, $zone->vung_trong_id, 'Kiểm thử');
        return $zone;
    }

    private function assignedSeason(TaiKhoanHeThong $user): MuaVu
    {
        return MuaVu::create([
            'vung_trong_id' => $this->assignedZone($user)->vung_trong_id,
            'san_pham_id' => DanhMucSanPham::firstOrFail()->san_pham_id,
            'ma_mua_vu' => 'MV-TEST-' . uniqid(), 'ten_mua_vu' => 'Mùa kiểm thử',
            'don_vi_san_luong' => 'kg', 'trang_thai' => 'DANG_CANH_TAC',
        ]);
    }
    private function getProducerUser(): TaiKhoanHeThong
    {
        $role = VaiTro::firstOrCreate(
            ['ma_vai_tro' => 'NGUOI_SAN_XUAT'],
            ['ten_vai_tro' => 'Người sản xuất', 'mo_ta' => 'Nông hộ / Hợp tác xã']
        );

        $org = ToChuc::firstOrCreate(
            ['ma_to_chuc' => 'TEST_ORG_01'],
            [
                'ten_to_chuc' => 'HTX Nông Nghiệp Thử Nghiệm',
                'loai_to_chuc' => 'HOP_TAC_XA',
                'dia_chi' => 'Đà Lạt, Lâm Đồng',
                'trang_thai' => 'DANG_HOAT_DONG',
            ]
        );

        $user = TaiKhoanHeThong::where('email', 'producer_test@example.com')->first();
        if (!$user) {
            $user = TaiKhoanHeThong::create([
                'vai_tro_id' => $role->vai_tro_id,
                'to_chuc_id' => $org->to_chuc_id,
                'ho_ten' => 'Nguyễn Văn Nông Dân',
                'email' => 'producer_test@example.com',
                'mat_khau_hash' => bcrypt('123456'),
                'trang_thai' => 'DANG_HOAT_DONG',
            ]);
        }

        return $user;
    }

    public function test_guest_cannot_access_producer_module(): void
    {
        $response = $this->get(route('producer.planting-zones.index'));
        $response->assertRedirect(route('auth.login.form'));
    }

    public function test_producer_dashboard_shows_producer_stats_and_nav(): void
    {
        $user = $this->getProducerUser();

        $response = $this->actingAs($user)->get(route('dashboard'));
        $response->assertStatus(200);
        $response->assertSee('Vùng trồng');
        $response->assertSee('Mùa vụ');
        $response->assertSee('Nhật ký canh tác');
    }

    public function test_producer_can_manage_planting_zones(): void
    {
        $user = $this->getProducerUser();

        // 1. Index
        $response = $this->actingAs($user)->get(route('producer.planting-zones.index'));
        $response->assertStatus(200);

        // 2. Create form
        $createResp = $this->actingAs($user)->get(route('producer.planting-zones.create'));
        $createResp->assertStatus(200);

        // 3. Store new planting zone
        $msvt = 'MSVT-TEST-' . time();
        $storeResp = $this->actingAs($user)->post(route('producer.planting-zones.store'), [
            'ten_vung_trong' => 'Vườn bưởi da xanh Test ' . time(),
            'ma_so_vung_trong' => $msvt,
            'dia_chi' => 'Xã Phú Lý, Huyện Vĩnh Cửu, Đồng Nai',
            'dien_tich_ha' => 2.5,
            'cay_trong_chinh' => 'Bưởi da xanh',
            'loai_chung_nhan' => 'VIETGAP',
            'so_chung_nhan' => 'VG-TEST-999',
            'don_vi_cap_chung_nhan' => 'Trung tâm Quacert',
            'ngay_cap_chung_nhan' => '2026-01-01',
            'ngay_het_han_chung_nhan' => '2028-01-01',
            'trang_thai' => 'DANG_SU_DUNG',
        ]);

        $zone = VungTrong::where('ma_so_vung_trong', $msvt)->first();
        $this->assertNotNull($zone);
        $storeResp->assertRedirect(route('producer.planting-zones.show', $zone->vung_trong_id));

        // 4. Show zone
        $showResp = $this->actingAs($user)->get(route('producer.planting-zones.show', $zone->vung_trong_id));
        $showResp->assertStatus(200);
        $showResp->assertSee($zone->ten_vung_trong);
        $showResp->assertSee('VIETGAP');

        // 5. Update zone
        $updateResp = $this->actingAs($user)->put(route('producer.planting-zones.update', $zone->vung_trong_id), [
            'ten_vung_trong' => 'Vườn bưởi da xanh Test Cập Nhật ' . time(),
            'ma_so_vung_trong' => $msvt,
            'dia_chi' => 'Xã Phú Lý, Huyện Vĩnh Cửu, Đồng Nai',
            'dien_tich_ha' => 3.0,
            'cay_trong_chinh' => 'Bưởi da xanh',
            'loai_chung_nhan' => 'VIETGAP',
            'trang_thai' => 'DANG_SU_DUNG',
        ]);
        $updateResp->assertRedirect(route('producer.planting-zones.show', $zone->vung_trong_id));
        $this->assertEquals(3.0, (float)$zone->fresh()->dien_tich_ha);
    }

    public function test_producer_can_manage_seasons(): void
    {
        $user = $this->getProducerUser();

        $zone = $this->assignedZone($user);
        if (!$zone) {
            $zone = VungTrong::create([
                'to_chuc_id' => $user->to_chuc_id,
                'ten_vung_trong' => 'Vườn Test Mùa Vụ ' . time(),
                'dia_chi' => 'Lâm Đồng',
                'dien_tich_ha' => 1.0,
                'trang_thai' => 'DANG_SU_DUNG',
            ]);
        }

        $product = DanhMucSanPham::first();

        // 1. Index
        $response = $this->actingAs($user)->get(route('producer.seasons.index'));
        $response->assertStatus(200);

        // 2. Create form
        $createResp = $this->actingAs($user)->get(route('producer.seasons.create', ['vung_trong_id' => $zone->vung_trong_id]));
        $createResp->assertStatus(200);

        // 3. Store season
        $seasonCode = 'MV-TEST-' . time();
        $storeResp = $this->actingAs($user)->post(route('producer.seasons.store'), [
            'vung_trong_id' => $zone->vung_trong_id,
            'san_pham_id' => $product->san_pham_id,
            'ma_mua_vu' => $seasonCode,
            'ten_mua_vu' => 'Vụ thu hoạch thử nghiệm ' . time(),
            'giong_cay_trong' => 'Giống chuẩn F1',
            'dien_tich_canh_tac_ha' => 0.8,
            'ngay_gieo' => '2026-08-01',
            'ngay_thu_hoach_du_kien' => '2026-11-01',
            'san_luong_du_kien' => 5000,
            'don_vi_san_luong' => 'kg',
            'trang_thai' => 'DANG_CANH_TAC',
        ]);

        $storeResp->assertSessionHasNoErrors();
        $storeResp->assertStatus(302);
        $season = MuaVu::where('ma_mua_vu', $seasonCode)->first();
        $this->assertNotNull($season);
        $storeResp->assertRedirect(route('producer.seasons.show', $season->mua_vu_id));

        // 4. Show season
        $showResp = $this->actingAs($user)->get(route('producer.seasons.show', $season->mua_vu_id));
        $showResp->assertStatus(200);
        $showResp->assertSee($seasonCode);
    }

    public function test_producer_can_record_and_revise_farming_log(): void
    {
        $user = $this->getProducerUser();
        $season = $this->assignedSeason($user);
        \Illuminate\Support\Facades\Storage::fake('public');
        $supply = DanhMucVatTu::first();

        // 1. Index
        $response = $this->actingAs($user)->get(route('producer.farming-logs.index'));
        $response->assertStatus(200);

        // 2. Store confirmed log with supply details
        $storeResp = $this->actingAs($user)->post(route('producer.farming-logs.store'), [
            'mua_vu_id' => $season->mua_vu_id,
            'loai_hoat_dong' => 'PHUN_THUOC',
            'ngay_thuc_hien' => '2026-10-20 08:30:00',
            'ghi_chu' => 'Phun phòng trừ rầy mềm',
            'tep_minh_chung' => \Illuminate\Http\UploadedFile::fake()->image('evidence.jpg'),
            'trang_thai' => 'DA_XAC_NHAN',
            'vat_tu' => [
                [
                    'vat_tu_id' => $supply->vat_tu_id,
                    'lieu_luong' => 2.5,
                    'don_vi_tinh' => 'lít',
                    'thoi_gian_cach_ly' => max(7, (int) $supply->thoi_gian_cach_ly_ngay),
                    'ghi_chu' => 'Pha tỷ lệ 1:500',
                ]
            ],
        ]);

        $storeResp->assertSessionHasNoErrors();
        $storeResp->assertStatus(302);
        $log = NhatKyCanhTac::where('mua_vu_id', $season->mua_vu_id)
            ->where('loai_hoat_dong', 'PHUN_THUOC')
            ->latest('nhat_ky_id')
            ->first();

        $this->assertNotNull($log);
        $this->assertEquals('DA_XAC_NHAN', $log->trang_thai);
        $this->assertCount(1, $log->chiTietVatTu);

        // 3. Show log
        $showResp = $this->actingAs($user)->get(route('producer.farming-logs.show', $log->nhat_ky_id));
        $showResp->assertStatus(200);
        $showResp->assertSee('Phun phòng trừ rầy mềm');

        // 4. Update confirmed log (creates a revised audit record)
        $updateResp = $this->actingAs($user)->put(route('producer.farming-logs.update', $log->nhat_ky_id), [
            'loai_hoat_dong' => 'PHUN_THUOC',
            'ngay_thuc_hien' => '2026-10-20 08:30:00',
            'ghi_chu' => 'Phun phòng trừ rầy mềm (đã đính chính)',
            'trang_thai' => 'DA_XAC_NHAN',
            'ly_do_sua' => 'Đính chính liều lượng chính xác',
            'vat_tu' => [
                [
                    'vat_tu_id' => $supply->vat_tu_id,
                    'lieu_luong' => 2.0,
                    'don_vi_tinh' => 'lít',
                    'thoi_gian_cach_ly' => max(7, (int) $supply->thoi_gian_cach_ly_ngay),
                    'ghi_chu' => 'Pha tỷ lệ 1:600',
                ]
            ],
        ]);

        $updateResp->assertSessionHasNoErrors();
        // Original record should be DA_THAY_THE
        $this->assertEquals('DA_THAY_THE', $log->fresh()->trang_thai);

        // New revised record should exist with ban_ghi_goc_id = $log->nhat_ky_id
        $revisedLog = NhatKyCanhTac::where('ban_ghi_goc_id', $log->nhat_ky_id)->first();
        $this->assertNotNull($revisedLog);
        $this->assertEquals('Đính chính liều lượng chính xác', $revisedLog->ly_do_sua);
    }

    public function test_producer_can_create_harvest_lot(): void
    {
        $user = $this->getProducerUser();
        $season = $this->assignedSeason($user);

        $lotCode = 'LO-TEST-' . time();
        $response = $this->actingAs($user)->post(route('producer.lots.store'), [
            'mua_vu_id' => $season->mua_vu_id,
            'san_pham_id' => $season->san_pham_id,
            'ma_lo' => $lotCode,
            'so_luong' => 750.5,
            'don_vi_tinh' => 'kg',
            'ngay_thu_hoach' => '2026-10-23',
            'han_su_dung' => '2026-10-30',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertStatus(302);
        $this->assertDatabaseHas('lo_nong_san', ['ma_lo' => $lotCode, 'mua_vu_id' => $season->mua_vu_id]);
        $this->assertEquals('DANG_THU_HOACH', $season->fresh()->trang_thai);

        $lotResp = $this->actingAs($user)->get(route('producer.lots.index'));
        $lotResp->assertStatus(200);
        $lotResp->assertSee($lotCode);
    }
}
