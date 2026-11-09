<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Truy xuất kiện hàng {{ $unit->ma_sscc }} · Farm2Fork</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="f2f-trace min-h-screen bg-stone-100 text-stone-900">
    <header class="bg-emerald-950 px-4 py-5 text-white">
        <div class="mx-auto max-w-3xl text-xl font-bold">Farm2Fork · Truy xuất kiện hàng</div>
    </header>
    <main class="mx-auto max-w-3xl space-y-5 px-4 py-6">
        <section class="rounded-xl bg-white p-5 shadow-sm">
            <h1 class="text-2xl font-bold">Kiện {{ $unit->loai_don_vi_label }}</h1>
            <p class="mt-2 break-all font-mono text-lg font-semibold">(00) {{ $unit->ma_sscc }}</p>
            <p class="mt-2">Trạng thái: <strong>{{ $unit->trang_thai_label }}</strong></p>
            @if($unit->is_demo)
                <p class="mt-3 rounded-lg bg-amber-50 p-3 text-amber-900">Mã định danh mô phỏng theo cấu trúc GS1, chưa phải mã được cấp để dùng thương mại.</p>
            @endif
        </section>
        <section class="rounded-xl bg-white p-5 shadow-sm">
            <h2 class="text-xl font-bold">Các lô trong kiện</h2>
            @foreach($unit->chiTiet as $item)
                @if($item->loNongSan)
                    <div class="mt-4 border-t border-stone-200 pt-4">
                        <p class="font-semibold">{{ $item->loNongSan->sanPham?->ten_san_pham ?? 'Sản phẩm' }}</p>
                        <p>Lô: {{ $item->loNongSan->ma_lo }} · {{ number_format($item->so_luong, 2) }} {{ $item->don_vi_tinh }}</p>
                        <a class="mt-2 inline-block font-semibold text-emerald-800 underline" href="{{ $item->loNongSan->digital_link_url }}">Xem nguồn gốc lô</a>
                    </div>
                @endif
            @endforeach
        </section>
    </main>
</body>
</html>
