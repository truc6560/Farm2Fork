<?php

namespace Tests\Feature;

use Tests\TestCase;

class TraceabilityDemoLotTest extends TestCase
{
    public function test_demo_lot77_traceability_page_loads_with_complete_stages(): void
    {
        $response = $this->get('/trace/TP-SPDA-20261106-DHA');

        $response->assertStatus(200);
        $response->assertSee('TP-SPDA-20261106-DHA');
        $response->assertSee('Dâu tây Đà Lạt');
        // Stage 1
        $response->assertSee('Vùng trồng & Nhật ký Canh tác Thực địa', false);
        $response->assertSee('Làm đất');
        $response->assertSee('Xuống giống');
        $response->assertSee('Chế phẩm dầu neem');
        $response->assertSee('Cách ly PHI 7 ngày');
        // Stage 2
        $response->assertSee('KD-261106-001');
        $response->assertSee('KD-261107-001');
        // Stage 3
        $response->assertSee('Đóng gói Logistics & Mã định danh GS1 SSCC', false);
        $response->assertSee('kiện SSCC');
        // Stage 4
        $response->assertSee('49C-118.72');
        $response->assertSee('Đèo Bảo Lộc');
        // Stage 5
        $response->assertSee('Giám sát Dữ liệu Cảm biến IoT Chuỗi Lạnh');
        $response->assertSee('20 phút');
        // Stage 6
        $response->assertSee('Điểm bán & Biên bản Tiếp nhận Phân phối', false);
        $response->assertSee('Công ty TNHH Chuỗi siêu thị Xanh Mart');
        $response->assertSee('Siêu thị Xanh Mart Flagship');
        $response->assertSee('399.99 kg');
        $response->assertSee('Nguyễn Thị Mai Phương');
        $response->assertSee('Nguyên vẹn');
    }

    public function test_gs1_digital_link_redirects_or_serves_trace_page(): void
    {
        $response = $this->get('/01/12000000000012/10/TP-SPDA-20261106-DHA');
        // Check either direct 200 or redirect to /trace/...
        if ($response->status() === 302) {
            $response->assertRedirect('/trace/TP-SPDA-20261106-DHA');
        } else {
            $response->assertStatus(200);
            $response->assertSee('TP-SPDA-20261106-DHA');
        }
    }
}
