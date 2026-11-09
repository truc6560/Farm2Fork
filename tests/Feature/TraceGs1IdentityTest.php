<?php

namespace Tests\Feature;

use App\Http\Controllers\TraceabilityController;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class TraceGs1IdentityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'trace_test', 'database.connections.trace_test' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
        ]]);
        Schema::create('danh_muc_san_pham', function (Blueprint $table) {
            $table->integer('san_pham_id')->primary();
            $table->string('gtin');
        });
        Schema::create('lo_nong_san', function (Blueprint $table) {
            $table->integer('lo_nong_san_id')->primary();
            $table->integer('san_pham_id');
            $table->string('ma_lo');
        });
        DB::table('danh_muc_san_pham')->insert(['san_pham_id' => 1, 'gtin' => '09506000134352']);
        DB::table('lo_nong_san')->insert(['lo_nong_san_id' => 1, 'san_pham_id' => 1, 'ma_lo' => 'LOT-TEST']);
    }

    public function test_matching_gtin_and_lot_resolve(): void
    {
        $controller = Mockery::mock(TraceabilityController::class)->makePartial();
        $request = Request::create('/trace/01/09506000134352/10/LOT-TEST');
        $controller->shouldReceive('show')->once()->with($request, 'LOT-TEST', 1)->andReturn(response('resolved'));
        $this->assertSame('resolved', $controller->showGs1($request, '09506000134352', 'LOT-TEST')->getContent());
    }

    public function test_same_lot_code_is_resolved_to_the_matching_product(): void
    {
        DB::table('danh_muc_san_pham')->insert(['san_pham_id' => 2, 'gtin' => '12000000000012']);
        DB::table('lo_nong_san')->insert(['lo_nong_san_id' => 2, 'san_pham_id' => 2, 'ma_lo' => 'LOT-TEST']);

        $controller = Mockery::mock(TraceabilityController::class)->makePartial();
        $request = Request::create('/01/12000000000012/10/LOT-TEST');
        $controller->shouldReceive('show')->once()->with($request, 'LOT-TEST', 2)->andReturn(response('matched product'));
        $this->assertSame('matched product', $controller->showGs1($request, '12000000000012', 'LOT-TEST')->getContent());
    }

    public function test_wrong_gtin_cannot_resolve_an_existing_lot(): void
    {
        $this->assertNotFound('00000000000000', 'LOT-TEST');
    }

    public function test_missing_lot_cannot_resolve_a_valid_product(): void
    {
        $this->assertNotFound('09506000134352', 'MISSING');
    }

    private function assertNotFound(string $gtin, string $lot): void
    {
        try {
            (new TraceabilityController())->showGs1(Request::create('/'), $gtin, $lot);
            $this->fail('An unknown GTIN/lot pair must not resolve.');
        } catch (HttpException $exception) {
            $this->assertSame(404, $exception->getStatusCode());
        }
    }
}
