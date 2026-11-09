<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\MuaVu;

$seasons = MuaVu::with(['vungTrong', 'sanPham'])->get();
echo "Total seasons: " . $seasons->count() . "\n";
foreach ($seasons as $s) {
    $q = $s->getThongTinCachLy();
    echo "Season #{$s->mua_vu_id}: {$s->ten_mua_vu} ({$s->trang_thai})\n";
    echo "  Dang cach ly: " . ($q['dang_cach_ly'] ? 'YES' : 'NO') . "\n";
    if ($q['dang_cach_ly']) {
        echo "  Vat tu: {$q['ten_vat_tu']}, Ngay het: {$q['ngay_het_cach_ly']}, Con lai: {$q['so_ngay_con_lai']} ngay\n";
    }
}

