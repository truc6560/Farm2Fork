<?php

namespace Tests\Feature;

use App\Models\LoNongSan;
use App\Models\NhatKyCanhTac;
use Tests\TestCase;

class TraceabilityMobileAndImageTest extends TestCase
{
    public function test_trace_page_renders_with_mobile_responsive_elements(): void
    {
        $lot = LoNongSan::first();
        if (!$lot) {
            $this->markTestSkipped('No lot found.');
        }

        $response = $this->get(route('trace.show', $lot->ma_lo));
        $response->assertStatus(200);

        // Check mobile responsive meta viewport
        $response->assertSee('viewport-fit=cover');
        
        // Check stage quick-jump navigation
        $response->assertSee('quy-trinh-canh-tac');
        $response->assertSee('quy-trinh-so-che-kiem-dinh');
        $response->assertSee('quy-trinh-dong-goi');
        $response->assertSee('quy-trinh-van-chuyen');
        $response->assertSee('quy-trinh-cam-bien-iot');
        $response->assertSee('quy-trinh-diem-ban');

        // Check interactive mobile modals & buttons
        $response->assertSee('id="imageLightboxModal"', false);
        $response->assertSee('id="backToTopBtn"', false);
        $response->assertSee('handleShare', false);
    }

    public function test_farming_log_image_can_be_served_successfully(): void
    {
        $logWithImage = NhatKyCanhTac::whereNotNull('tep_minh_chung')->first();
        if (!$logWithImage) {
            $this->markTestSkipped('No farming log with image found.');
        }

        $url = '/storage/' . ltrim($logWithImage->tep_minh_chung, '/');
        $response = $this->get($url);
        $response->assertStatus(200);
        $this->assertStringStartsWith('image/', $response->headers->get('Content-Type'));
    }
}
