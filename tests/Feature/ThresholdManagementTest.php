<?php

namespace Tests\Feature;

use App\Models\CauHinhNguongCanhBao;
use App\Models\KhoLanh;
use App\Models\TaiKhoanHeThong;
use App\Models\VaiTro;
use Tests\TestCase;

class ThresholdManagementTest extends TestCase
{
    private TaiKhoanHeThong $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = VaiTro::firstOrCreate(
            ['ma_vai_tro' => 'ADMIN'],
            ['ten_vai_tro' => 'Quản trị viên', 'mo_ta' => 'Quản trị toàn hệ thống']
        );

        $this->adminUser = TaiKhoanHeThong::where('vai_tro_id', $adminRole->vai_tro_id)->first()
            ?? TaiKhoanHeThong::factory()->create([
                'vai_tro_id' => $adminRole->vai_tro_id,
                'email' => 'admin_threshold_test@farm2fork.vn',
                'trang_thai' => 'DANG_HOAT_DONG',
            ]);
    }

    public function test_admin_can_view_threshold_list_with_edit_buttons(): void
    {
        $response = $this->actingAs($this->adminUser)->get(route('admin.thresholds.index'));
        $response->assertStatus(200);
        $response->assertSee('Thêm ngưỡng cảnh báo kho lạnh');
        $response->assertSee('Chỉnh sửa');
    }

    public function test_admin_can_view_threshold_edit_form(): void
    {
        $threshold = CauHinhNguongCanhBao::first();
        if (!$threshold) {
            $warehouse = KhoLanh::first();
            $threshold = CauHinhNguongCanhBao::create([
                'kho_lanh_id' => $warehouse->kho_lanh_id,
                'nhiet_do_min' => 0.0,
                'nhiet_do_max' => 5.0,
                'do_am_min' => 85.0,
                'do_am_max' => 95.0,
                'thoi_gian_vuot_nguong_phut' => 15,
                'trang_thai' => 'HIEU_LUC',
                'mo_ta' => 'Ngưỡng kiểm thử tự động',
            ]);
        }

        $response = $this->actingAs($this->adminUser)->get(route('admin.thresholds.edit', $threshold->cau_hinh_id));
        $response->assertStatus(200);
        $response->assertSee('Chỉnh Sửa Ngưỡng Cảnh Báo');
        $response->assertSee((string)$threshold->nhiet_do_min);
        $response->assertSee((string)$threshold->nhiet_do_max);
    }

    public function test_admin_can_update_threshold(): void
    {
        $threshold = CauHinhNguongCanhBao::first();
        if (!$threshold) {
            $warehouse = KhoLanh::first();
            $threshold = CauHinhNguongCanhBao::create([
                'kho_lanh_id' => $warehouse->kho_lanh_id,
                'nhiet_do_min' => 1.0,
                'nhiet_do_max' => 6.0,
                'do_am_min' => 80.0,
                'do_am_max' => 90.0,
                'thoi_gian_vuot_nguong_phut' => 15,
                'trang_thai' => 'HIEU_LUC',
                'mo_ta' => 'Trước khi sửa',
            ]);
        }

        $newWarehouse = KhoLanh::first();

        $response = $this->actingAs($this->adminUser)->put(route('admin.thresholds.update', $threshold->cau_hinh_id), [
            'kho_lanh_id' => $newWarehouse->kho_lanh_id,
            'nhiet_do_min' => 1.5,
            'nhiet_do_max' => 7.5,
            'do_am_min' => 82.0,
            'do_am_max' => 92.0,
            'thoi_gian_vuot_nguong_phut' => 20,
            'trang_thai' => 'HIEU_LUC',
            'mo_ta' => 'Quy chuẩn cập nhật mới nhất',
        ]);

        $response->assertRedirect(route('admin.thresholds.index'));
        $response->assertSessionHas('status', 'Đã cập nhật cấu hình ngưỡng cảnh báo thành công.');

        $this->assertDatabaseHas('cau_hinh_nguong_canh_bao', [
            'cau_hinh_id' => $threshold->cau_hinh_id,
            'mo_ta' => 'Quy chuẩn cập nhật mới nhất',
            'thoi_gian_vuot_nguong_phut' => 20,
        ]);
    }

    public function test_update_threshold_validation_fails_when_max_temp_not_greater_than_min(): void
    {
        $threshold = CauHinhNguongCanhBao::first();
        if (!$threshold) {
            $this->markTestSkipped('No threshold to test validation.');
        }

        $response = $this->actingAs($this->adminUser)->put(route('admin.thresholds.update', $threshold->cau_hinh_id), [
            'kho_lanh_id' => $threshold->kho_lanh_id,
            'nhiet_do_min' => 10.0,
            'nhiet_do_max' => 5.0, // Invalid: max <= min
            'do_am_min' => 80.0,
            'do_am_max' => 90.0,
            'thoi_gian_vuot_nguong_phut' => 15,
            'trang_thai' => 'HIEU_LUC',
        ]);

        $response->assertSessionHasErrors(['nhiet_do_max']);
    }
}
