<?php

namespace Tests\Feature;

use App\Models\DanhMucSanPham;
use App\Models\KhoLanh;
use App\Models\LoNongSan;
use App\Models\MuaVu;
use App\Models\NhatKyCanhTac;
use App\Models\TaiKhoanHeThong;
use App\Models\VungTrong;
use Illuminate\Support\Str;
use Tests\TestCase;

class ChainSeasonAndLotTest extends TestCase
{
    private TaiKhoanHeThong $chainUser;

    protected function setUp(): void
    {
        parent::setUp();
        // User with QUAN_LY_CHUOI role
        $this->chainUser = TaiKhoanHeThong::whereHas('vaiTro', fn($q) => $q->where('ma_vai_tro', 'QUAN_LY_CHUOI'))->first()
            ?? TaiKhoanHeThong::first();
    }

    public function test_cold_storage_page_renders_cleanly(): void
    {
        $response = $this->actingAs($this->chainUser)->get(route('chain.cold-storage.index'));
        $response->assertStatus(200);
        $response->assertDontSee('(1 lô)');
    }

    public function test_lot_index_scopes_counts_to_selected_warehouse(): void
    {
        $wh = KhoLanh::whereIn('trang_thai', ['DANG_HOAT_DONG', 'HOAT_DONG'])->first();
        if (!$wh) {
            $this->markTestSkipped('No warehouse found.');
        }

        $response = $this->actingAs($this->chainUser)->get(route('chain.lots.index', ['kho_lanh_id' => $wh->kho_lanh_id]));
        $response->assertStatus(200);
        $response->assertSee('Đang lọc theo kho lạnh:');
        $response->assertSee($wh->ten_kho);
    }

    public function test_season_index_displays_kpis_and_actions(): void
    {
        $response = $this->actingAs($this->chainUser)->get(route('chain.seasons.index'));
        $response->assertStatus(200);
        $response->assertSee('Tổng số mùa vụ');
        $response->assertSee('Đang canh tác');
        $response->assertSee('Đang thu hoạch');
        $response->assertSee('Khai báo mùa vụ mới');
    }

    public function test_season_show_page_displays_details(): void
    {
        $season = MuaVu::first();
        if (!$season) {
            $this->markTestSkipped('No season found.');
        }

        $response = $this->actingAs($this->chainUser)->get(route('chain.seasons.show', $season->mua_vu_id));
        $response->assertStatus(200);
        $response->assertSee($season->ten_mua_vu);
        $response->assertSee('Hồ sơ mùa vụ');
    }

    public function test_season_create_page_renders(): void
    {
        $response = $this->actingAs($this->chainUser)->get(route('chain.seasons.create'));
        $response->assertStatus(200);
        $response->assertSee('Khai Báo Mùa Vụ Mới');
        $response->assertSee('Vùng trồng liên kết');
    }

    public function test_season_store_validation_and_creation(): void
    {
        $zone = VungTrong::where('trang_thai', 'DANG_SU_DUNG')->first();
        $product = DanhMucSanPham::where('trang_thai', 'DANG_SU_DUNG')->first();

        if (!$zone || !$product) {
            $this->markTestSkipped('No zone or product available.');
        }

        // 1. Validation test: harvest date before start date
        $invalidData = [
            'vung_trong_id' => $zone->vung_trong_id,
            'san_pham_id' => $product->san_pham_id,
            'ngay_gieo' => '2026-10-01',
            'ngay_thu_hoach_du_kien' => '2026-09-01', // Invalid
            'don_vi_san_luong' => 'kg',
            'trang_thai' => 'DANG_CANH_TAC',
        ];
        $response = $this->actingAs($this->chainUser)->post(route('chain.seasons.store'), $invalidData);
        $response->assertSessionHasErrors('ngay_thu_hoach_du_kien');

        // 2. Valid creation test
        $code = 'MV-TEST-' . strtoupper(Str::random(5));
        $validData = [
            'vung_trong_id' => $zone->vung_trong_id,
            'san_pham_id' => $product->san_pham_id,
            'ma_mua_vu' => $code,
            'ten_mua_vu' => 'Mùa vụ kiểm thử tự động ' . $code,
            'giong_cay_trong' => 'Giống kiểm thử F1',
            'dien_tich_canh_tac_ha' => 1.25,
            'ngay_gieo' => '2026-11-01',
            'ngay_thu_hoach_du_kien' => '2026-12-01',
            'san_luong_du_kien' => 3500,
            'don_vi_san_luong' => 'kg',
            'trang_thai' => 'DANG_CANH_TAC',
        ];

        $response = $this->actingAs($this->chainUser)->post(route('chain.seasons.store'), $validData);
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('mua_vu', ['ma_mua_vu' => $code]);

        $createdSeason = MuaVu::where('ma_mua_vu', $code)->first();
        $this->assertNotNull($createdSeason);

        // 3. Edit page renders
        $editResponse = $this->actingAs($this->chainUser)->get(route('chain.seasons.edit', $createdSeason->mua_vu_id));
        $editResponse->assertStatus(200);
        $editResponse->assertSee($code);

        // 4. Update season
        $updateData = [
            'vung_trong_id' => $zone->vung_trong_id,
            'san_pham_id' => $product->san_pham_id,
            'ma_mua_vu' => $code,
            'ten_mua_vu' => 'Mùa vụ kiểm thử tự động đã sửa ' . $code,
            'giong_cay_trong' => 'Giống F2',
            'dien_tich_canh_tac_ha' => 2.0,
            'ngay_gieo' => '2026-11-01',
            'ngay_thu_hoach_du_kien' => '2026-12-15',
            'san_luong_du_kien' => 4000,
            'don_vi_san_luong' => 'kg',
            'trang_thai' => 'DANG_THU_HOACH',
        ];
        $updateResp = $this->actingAs($this->chainUser)->put(route('chain.seasons.update', $createdSeason->mua_vu_id), $updateData);
        $updateResp->assertSessionHasNoErrors();
        $this->assertDatabaseHas('mua_vu', [
            'mua_vu_id' => $createdSeason->mua_vu_id,
            'ten_mua_vu' => 'Mùa vụ kiểm thử tự động đã sửa ' . $code,
            'trang_thai' => 'DANG_THU_HOACH',
        ]);

        // 5. Delete empty season successfully
        $deleteResp = $this->actingAs($this->chainUser)->delete(route('chain.seasons.destroy', $createdSeason->mua_vu_id));
        $deleteResp->assertRedirect(route('chain.seasons.index'));
        $this->assertDatabaseMissing('mua_vu', ['mua_vu_id' => $createdSeason->mua_vu_id]);
    }

    public function test_season_with_lots_cannot_be_deleted(): void
    {
        // Find a season that has harvest lots
        $seasonWithLots = MuaVu::whereHas('loNongSan')->first();
        if (!$seasonWithLots) {
            $this->markTestSkipped('No season with lots found.');
        }

        $response = $this->actingAs($this->chainUser)->delete(route('chain.seasons.destroy', $seasonWithLots->mua_vu_id));
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('mua_vu', ['mua_vu_id' => $seasonWithLots->mua_vu_id]);
    }
}
