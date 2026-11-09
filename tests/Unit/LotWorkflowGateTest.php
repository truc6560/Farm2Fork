<?php

namespace Tests\Unit;

use App\Services\LotStatusService;
use PHPUnit\Framework\TestCase;

class LotWorkflowGateTest extends TestCase
{
    public function test_required_quality_gates_cannot_be_skipped(): void
    {
        $states = new LotStatusService();

        $this->assertTrue($states->canTransition('MOI_TAO', 'CHO_KIEM_DINH'));
        $this->assertFalse($states->canTransition('MOI_TAO', 'CHO_SO_CHE'));
        $this->assertFalse($states->canTransition('CHO_KIEM_DINH', 'DA_SO_CHE'));
        $this->assertTrue($states->canTransition('CHO_KIEM_DINH', 'CHO_SO_CHE'));
        $this->assertFalse($states->canTransition('DA_SO_CHE', 'DAT_KIEM_DINH'));
        $this->assertTrue($states->canTransition('DA_SO_CHE', 'CHO_KIEM_DINH_XUAT_XUONG'));
        $this->assertTrue($states->canTransition('CHO_KIEM_DINH_XUAT_XUONG', 'DAT_KIEM_DINH'));
        $this->assertFalse($states->canTransition('CHO_KIEM_DINH_XUAT_XUONG', 'TRONG_KHO'));
    }
}
