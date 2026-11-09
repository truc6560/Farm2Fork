<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>In {{ $units->count() }} tem SSCC · Farm2Fork</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; background: #eef2ee; color: #17271d; font-family: Arial, sans-serif; }
        .print-toolbar { position: sticky; top: 0; z-index: 10; display: flex; align-items: center; justify-content: space-between; gap: 16px; padding: 12px 18px; background: #173d27; color: #fff; box-shadow: 0 3px 16px #0003; }
        .print-toolbar strong { display: block; font-size: 16px; }
        .print-toolbar span { font-size: 12px; color: #d9e8dd; }
        .print-actions { display: flex; gap: 8px; }
        .print-actions :is(a, button) { min-height: 42px; display: inline-flex; align-items: center; justify-content: center; padding: 8px 14px; border: 1px solid #d6e5db; border-radius: 9px; background: #fff; color: #173d27; font: inherit; font-size: 14px; font-weight: 700; text-decoration: none; cursor: pointer; }
        .print-actions button { background: #2f8a55; color: #fff; border-color: #2f8a55; }
        .label-list { display: grid; gap: 18px; justify-content: center; padding: 22px; }
        .logistic-label { width: 100mm; min-height: 145mm; display: grid; grid-template-rows: auto auto 1fr auto; gap: 4mm; padding: 7mm; border: .6mm solid #111; background: #fff; page-break-after: always; break-after: page; }
        .logistic-label:last-child { page-break-after: auto; break-after: auto; }
        .label-head { display: grid; grid-template-columns: minmax(0, 1fr) 25mm; gap: 4mm; padding-bottom: 3mm; border-bottom: .5mm solid #111; }
        .brand { font-size: 15pt; font-weight: 800; }
        .label-type { margin-top: 1.5mm; font-size: 9pt; font-weight: 700; }
        .demo { margin-bottom: 1mm; color: #9a5a00; font-size: 8pt; font-weight: 800; }
        .qr { width: 25mm; height: 25mm; display: grid; place-items: center; }
        .qr svg { width: 100%; height: 100%; }
        .label-meta { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 2mm 5mm; padding-bottom: 3mm; border-bottom: .5mm solid #111; }
        .field { min-width: 0; }
        .field span { display: block; color: #555; font-size: 7.5pt; font-weight: 700; text-transform: uppercase; }
        .field strong { display: block; margin-top: .7mm; font-size: 9pt; overflow-wrap: anywhere; }
        .content { display: grid; align-content: start; gap: 2mm; }
        .content-title { font-size: 8pt; font-weight: 800; text-transform: uppercase; }
        .content-row { display: grid; grid-template-columns: minmax(0, 1fr) auto; gap: 3mm; padding: 2mm 0; border-bottom: .25mm solid #bbb; font-size: 9pt; }
        .content-row code { display: block; margin-top: .5mm; color: #444; font-size: 7.5pt; }
        .barcode-block { text-align: center; }
        .barcode { width: 100%; max-height: 35mm; overflow: hidden; }
        .barcode svg { width: 100%; height: 31mm; }
        .hri { margin-top: 1mm; font-family: Consolas, monospace; font-size: 15pt; font-weight: 800; letter-spacing: .06em; }
        .barcode-caption { margin-top: 1mm; font-size: 7pt; }
        @media (max-width: 600px) {
            .print-toolbar { align-items: stretch; flex-direction: column; }
            .print-actions > * { flex: 1; }
            .label-list { padding: 10px; overflow-x: auto; justify-content: start; }
            .logistic-label { transform-origin: top left; }
        }
        .screen-message { max-width: 100mm; margin: 16px auto 0; padding: 10px 14px; border: 1px solid #a9d1b5; border-radius: 10px; background: #eff9f1; color: #175e37; font-size: 13px; }
        @media print {
            @page { size: 100mm 150mm; margin: 0; }
            body { background: #fff; }
            .print-toolbar { display: none !important; }
            .screen-message { display: none !important; }
            .label-list { display: block; padding: 0; }
            .logistic-label { margin: 0; border-width: .45mm; }
        }
    </style>
</head>
<body>
    <header class="print-toolbar">
        <div><strong>{{ $units->count() }} tem logistics đã sẵn sàng</strong><span>Mỗi kiện có một SSCC riêng · Khổ tem 100 × 150 mm</span></div>
        <div class="print-actions">
            <a href="{{ route('processing.packaging.index') }}">Quay lại</a>
            <button type="button" onclick="window.print()">In tất cả tem</button>
        </div>
    </header>

    @if(session('status'))
        <div class="screen-message">{{ session('status') }}</div>
    @endif

    <main class="label-list">
        @foreach($units as $unit)
            <article class="logistic-label">
                <header class="label-head">
                    <div>
                        @if($unit->is_demo)<div class="demo">MÃ MÔ PHỎNG · KHÔNG DÙNG THƯƠNG MẠI</div>@endif
                        <div class="brand">FARM2FORK</div>
                        <div class="label-type">NHÃN LOGISTICS · {{ mb_strtoupper($unit->loai_don_vi_label) }}</div>
                    </div>
                    <div class="qr">{!! \App\Services\QrCodeService::svg(app(\App\Services\Gs1DigitalLinkService::class)->ssccUrl($unit->ma_sscc), 110, 4) !!}</div>
                </header>

                <section class="label-meta">
                    <div class="field"><span>Ngày đóng gói</span><strong>{{ $unit->ngay_dong_goi?->format('d/m/Y H:i') ?? '—' }}</strong></div>
                    <div class="field"><span>Kho bảo quản</span><strong>{{ $unit->khoLanh?->ten_kho ?? 'Chưa chỉ định' }}</strong></div>
                    <div class="field"><span>Trạng thái</span><strong>{{ $unit->trang_thai_label }}</strong></div>
                    <div class="field"><span>Đơn vị tạo kiện</span><strong>Farm2Fork Supply Chain</strong></div>
                </section>

                <section class="content">
                    <div class="content-title">Hàng hóa bên trong kiện</div>
                    @foreach($unit->chiTiet as $item)
                        <div class="content-row">
                            <div><strong>{{ $item->loNongSan?->sanPham?->ten_san_pham ?? 'Sản phẩm chưa xác định' }}</strong><code>Lô {{ $item->loNongSan?->ma_lo ?? '—' }}</code></div>
                            <strong>{{ number_format($item->so_luong, 2) }} {{ $item->don_vi_tinh }}</strong>
                        </div>
                    @endforeach
                </section>

                <footer class="barcode-block">
                    <div class="barcode">{!! $unit->barcode_svg !!}</div>
                    <div class="hri">{{ $unit->hri }}</div>
                    <div class="barcode-caption">SERIAL SHIPPING CONTAINER CODE (SSCC) · GS1-128</div>
                </footer>
            </article>
        @endforeach
    </main>
</body>
</html>
