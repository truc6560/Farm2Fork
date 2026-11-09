<?php

namespace Tests\Feature;

use App\Models\TaiKhoanHeThong;
use App\Models\ToChuc;
use App\Models\VaiTro;
use App\Models\VungTrong;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PlantingZoneManagementTest extends TestCase
{
    private function createProducerUser(string $orgCode, string $email): array
    {
        $role = VaiTro::firstOrCreate(
            ['ma_vai_tro' => 'NGUOI_SAN_XUAT'],
            ['ten_vai_tro' => 'Người sản xuất', 'mo_ta' => 'Nông hộ / Hợp tác xã']
        );

        $org = ToChuc::firstOrCreate(
            ['ma_to_chuc' => $orgCode],
            [
                'ten_to_chuc' => 'Tổ chức ' . $orgCode,
                'loai_to_chuc' => 'HOP_TAC_XA',
                'dia_chi' => 'Lâm Đồng',
                'trang_thai' => 'DANG_HOAT_DONG',
            ]
        );

        $user = TaiKhoanHeThong::firstOrCreate(
            ['email' => $email],
            [
                'vai_tro_id' => $role->vai_tro_id,
                'to_chuc_id' => $org->to_chuc_id,
                'ho_ten' => 'Nông Dân ' . $orgCode,
                'mat_khau_hash' => bcrypt('123456'),
                'trang_thai' => 'DANG_HOAT_DONG',
            ]
        );

        return [$user, $org];
    }

    public function test_producer_can_create_zone_by_typing_msvt_directly()
    {
        [$user, $org] = $this->createProducerUser('TEST_ORG_A', 'farmer_a_' . time() . '@example.com');

        $msvt = 'PUC-TEST-' . time();
        $response = $this->actingAs($user)->post(route('producer.planting-zones.store'), [
            'ma_so_vung_trong' => $msvt,
            'cay_trong_chinh' => 'Dâu tây New Zealand',
            'dien_tich_ha' => 1.750,
            'dia_chi' => 'Thôn Thái Phiên, Phường 12, Đà Lạt',
            'trang_thai' => 'DANG_SU_DUNG',
        ]);

        $zone = VungTrong::where('ma_so_vung_trong', $msvt)->first();
        $this->assertNotNull($zone);
        $this->assertEquals($msvt, $zone->ma_so_vung_trong);
        $this->assertEquals('Dâu tây New Zealand', $zone->cay_trong_chinh);
        $this->assertEquals(1.75, (float)$zone->dien_tich_ha);
        $this->assertEquals($org->to_chuc_id, $zone->to_chuc_id);
        $this->assertStringStartsWith('F2F-VT-', $zone->ma_noi_bo);

        $response->assertRedirect(route('producer.planting-zones.show', $zone->vung_trong_id));

        // Clean up
        $zone->delete();
    }

    public function test_producer_can_create_zone_by_uploading_certificate_without_msvt()
    {
        Storage::fake('public');
        [$user, $org] = $this->createProducerUser('TEST_ORG_B', 'farmer_b_' . time() . '@example.com');

        $fakeFile = UploadedFile::fake()->create('chung_nhan_msvt.pdf', 500, 'application/pdf');

        $response = $this->actingAs($user)->post(route('producer.planting-zones.store'), [
            'cay_trong_chinh' => 'Xà lách thủy canh',
            'dien_tich_ha' => 0.850,
            'dia_chi' => 'Đường Vạn Hạnh, Phường 8, Đà Lạt',
            'trang_thai' => 'DANG_SU_DUNG',
            'tep_chung_nhan' => $fakeFile,
        ]);

        $zone = VungTrong::where('to_chuc_id', $org->to_chuc_id)
            ->where('cay_trong_chinh', 'Xà lách thủy canh')
            ->latest('vung_trong_id')
            ->first();

        $this->assertNotNull($zone);
        $this->assertNull($zone->ma_so_vung_trong);
        $this->assertNotNull($zone->tep_chung_nhan);
        $this->assertEquals('Xà lách thủy canh', $zone->cay_trong_chinh);
        $this->assertStringStartsWith('F2F-VT-', $zone->ma_noi_bo);

        $response->assertRedirect(route('producer.planting-zones.show', $zone->vung_trong_id));

        // Clean up
        $zone->delete();
    }

    public function test_rejects_when_both_msvt_and_certificate_are_missing()
    {
        [$user, $org] = $this->createProducerUser('TEST_ORG_C', 'farmer_c_' . time() . '@example.com');

        $response = $this->actingAs($user)->post(route('producer.planting-zones.store'), [
            'cay_trong_chinh' => 'Cà chua bi',
            'dien_tich_ha' => 1.0,
            'dia_chi' => 'Đà Lạt',
            'trang_thai' => 'DANG_SU_DUNG',
        ]);

        $response->assertSessionHasErrors(['ma_so_vung_trong', 'tep_chung_nhan']);
    }

    public function test_rejects_invalid_area_or_missing_crop()
    {
        [$user, $org] = $this->createProducerUser('TEST_ORG_D', 'farmer_d_' . time() . '@example.com');

        // Area <= 0
        $res1 = $this->actingAs($user)->post(route('producer.planting-zones.store'), [
            'ma_so_vung_trong' => 'PUC-INVALID-AREA-' . time(),
            'cay_trong_chinh' => 'Dưa lưới',
            'dien_tich_ha' => 0,
            'dia_chi' => 'Đà Lạt',
            'trang_thai' => 'DANG_SU_DUNG',
        ]);
        $res1->assertSessionHasErrors(['dien_tich_ha']);

        // Missing crop
        $res2 = $this->actingAs($user)->post(route('producer.planting-zones.store'), [
            'ma_so_vung_trong' => 'PUC-MISSING-CROP-' . time(),
            'dien_tich_ha' => 2.0,
            'dia_chi' => 'Đà Lạt',
            'trang_thai' => 'DANG_SU_DUNG',
        ]);
        $res2->assertSessionHasErrors(['cay_trong_chinh']);
    }

    public function test_producer_cannot_view_or_edit_planting_zone_of_another_organization()
    {
        [$user1, $org1] = $this->createProducerUser('ORG_OWNER_1', 'owner1_' . time() . '@example.com');
        [$user2, $org2] = $this->createProducerUser('ORG_OWNER_2', 'owner2_' . time() . '@example.com');

        $zone = VungTrong::create([
            'to_chuc_id' => $org1->to_chuc_id,
            'nguoi_phu_trach_id' => $user1->tai_khoan_id,
            'ma_so_vung_trong' => 'PUC-SECURE-' . time(),
            'ten_vung_trong' => 'Vùng bảo mật Org 1',
            'dia_chi' => 'Đà Lạt',
            'dien_tich_ha' => 1.5,
            'cay_trong_chinh' => 'Bơ sáp',
            'trang_thai' => 'DANG_SU_DUNG',
        ]);

        // User2 attempts to view Zone of Org 1 -> 403 Forbidden
        $viewResp = $this->actingAs($user2)->get(route('producer.planting-zones.show', $zone->vung_trong_id));
        $viewResp->assertStatus(403);

        // User2 attempts to edit Zone of Org 1 -> 403 Forbidden
        $editResp = $this->actingAs($user2)->get(route('producer.planting-zones.edit', $zone->vung_trong_id));
        $editResp->assertStatus(403);

        // User2 attempts to update Zone of Org 1 -> 403 Forbidden
        $updateResp = $this->actingAs($user2)->put(route('producer.planting-zones.update', $zone->vung_trong_id), [
            'cay_trong_chinh' => 'Cố tình sửa',
            'dien_tich_ha' => 2.0,
            'dia_chi' => 'Hacker address',
            'trang_thai' => 'DANG_SU_DUNG',
        ]);
        $updateResp->assertStatus(403);

        // Clean up
        $zone->delete();
    }

    public function test_danh_sach_34_tinh_thanh_quy_chuan()
    {
        $danhSach = VungTrong::DANH_SACH_MA_TINH;
        $this->assertCount(34, $danhSach);

        // Verify key provinces from official list
        $expected = [
            '01' => 'Thành phố Hà Nội',
            '04' => 'Cao Bằng',
            '08' => 'Tuyên Quang',
            '11' => 'Điện Biên',
            '12' => 'Lai Châu',
            '14' => 'Sơn La',
            '15' => 'Lào Cai',
            '19' => 'Thái Nguyên',
            '20' => 'Lạng Sơn',
            '22' => 'Quảng Ninh',
            '24' => 'Bắc Ninh',
            '25' => 'Phú Thọ',
            '31' => 'Thành phố Hải Phòng',
            '33' => 'Hưng Yên',
            '37' => 'Ninh Bình',
            '38' => 'Thanh Hóa',
            '40' => 'Nghệ An',
            '42' => 'Hà Tĩnh',
            '44' => 'Quảng Trị',
            '46' => 'Thành phố Huế',
            '48' => 'Thành phố Đà Nẵng',
            '51' => 'Quảng Ngãi',
            '52' => 'Gia Lai',
            '56' => 'Khánh Hòa',
            '66' => 'Đắk Lắk',
            '68' => 'Lâm Đồng',
            '75' => 'Đồng Nai',
            '79' => 'Thành phố Hồ Chí Minh',
            '80' => 'Tây Ninh',
            '82' => 'Đồng Tháp',
            '86' => 'Vĩnh Long',
            '91' => 'An Giang',
            '92' => 'Thành phố Cần Thơ',
            '96' => 'Cà Mau',
        ];

        $this->assertSame($expected, $danhSach);
        $this->assertSame($expected, config('provinces.danh_sach'));

        // Test helper methods
        $this->assertEquals('Lâm Đồng', VungTrong::getTenTinhThanh('68'));
        $this->assertEquals('Thành phố Hà Nội', VungTrong::getTenTinhThanh('01'));
        $this->assertEquals('Cà Mau', VungTrong::getTenTinhThanh('96'));
        $this->assertNull(VungTrong::getTenTinhThanh('999'));

        // Test model accessor
        $zone = new VungTrong(['ma_so_vung_trong' => '68-PUC-DAUTAY-00001']);
        $this->assertEquals('Lâm Đồng', $zone->ten_tinh_thanh);
    }
}
