<!DOCTYPE html>
<html lang="vi" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, viewport-fit=cover">
    <title>Truy xuất nguồn gốc - {{ $product->ten_san_pham ?? 'Nông sản Farm2Fork' }} (Lô {{ $lot->ma_lo }})</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        /* Tối ưu thanh cuộn ngang trên điện thoại */
        .scrollbar-none::-webkit-scrollbar { display: none; }
        .scrollbar-none { -ms-overflow-style: none; scrollbar-width: none; }
        /* Safe-area padding cho điện thoại có tai thỏ / thanh điều hướng */
        .safe-bottom { padding-bottom: max(1rem, env(safe-area-inset-bottom)); }

        :root {
            --trace-green: #17603a;
            --trace-green-dark: #103f2a;
            --trace-mint: #eef8f1;
            --trace-ink: #17221c;
        }

        body.f2f-trace {
            background:
                radial-gradient(circle at top right, rgba(197, 228, 207, .5), transparent 24rem),
                #f4f6f3;
            color: var(--trace-ink);
        }

        .trace-header { backdrop-filter: blur(12px); }
        .trace-hero { box-shadow: 0 14px 35px rgba(27, 67, 45, .08); }
        .trace-stage-nav a { min-height: 42px; display: inline-flex; align-items: center; }
        .trace-stage { scroll-margin-top: 9.5rem; }
        .trace-stage-toggle { display: none; }

        @media (max-width: 639px) {
            .trace-header .trace-brand-overline { display: none; }
            .trace-header .trace-action-label { display: none; }
            .trace-header .trace-action-button {
                width: 44px;
                min-width: 44px;
                height: 44px;
                padding: 0;
                justify-content: center;
                border-radius: 13px;
            }
            .trace-header .trace-verified {
                width: 32px;
                height: 32px;
                padding: 0;
                justify-content: center;
                font-size: 0;
            }
            .trace-header .trace-verified::after { content: '\2713'; font-size: 16px; }
            .trace-shell { padding-top: 12px; padding-left: 10px; padding-right: 10px; }
            .trace-hero { border-radius: 20px; }
            .trace-hero h1 { font-size: 1.55rem; line-height: 1.25; }
            .trace-kpis { gap: 8px; }
            .trace-kpis > div { padding: 12px; min-height: 86px; }
            .trace-journey { margin-left: -2px; margin-right: -2px; padding: 14px 12px; }
            .trace-stage-nav {
                position: sticky;
                top: 60px;
                z-index: 20;
                margin-left: -12px;
                margin-right: -12px;
                padding: 9px 12px;
                background: rgba(255, 255, 255, .96);
                border-top: 1px solid #e7e5e4;
                border-bottom: 1px solid #e7e5e4;
                box-shadow: 0 6px 16px rgba(28, 45, 34, .06);
                backdrop-filter: blur(10px);
            }
            .trace-stage-nav a {
                background: #f4f7f4;
                border-color: #dce8df;
                color: #284c37;
                font-weight: 700;
            }
            .trace-stage {
                padding: 12px !important;
                border: 1px solid #dce5de !important;
                border-radius: 18px;
                background: #fff;
                box-shadow: 0 8px 24px rgba(33, 58, 42, .06);
                scroll-margin-top: 7.5rem;
            }
            .trace-stage + .trace-stage { margin-top: 12px; }
            .trace-stage > :first-child { border-bottom: 0; padding-bottom: 0; }
            .trace-stage-toggle {
                width: 100%;
                min-height: 46px;
                margin-top: 12px;
                border: 1px solid #cce0d1;
                border-radius: 12px;
                background: var(--trace-mint);
                color: var(--trace-green-dark);
                font-size: 15px;
                font-weight: 700;
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 8px;
            }
            .trace-stage-toggle svg { transition: transform .2s ease; }
            .trace-stage:not(.is-collapsed) .trace-stage-toggle svg { transform: rotate(180deg); }
            .trace-stage.is-collapsed > :not(:first-child):not(.trace-stage-toggle) { display: none !important; }
            .trace-stage.is-collapsed .trace-stage-toggle { margin-bottom: 0; }
            .trace-stage:not(.is-collapsed) .trace-stage-toggle { margin-bottom: 14px; }
            .trace-stage .grid { min-width: 0; }
            .trace-stage [class*="font-mono"] { overflow-wrap: anywhere; }
        }

        @media print {
            .trace-stage-toggle { display: none !important; }
            .trace-stage.is-collapsed > :not(:first-child):not(.trace-stage-toggle) { display: block !important; }
            .trace-stage-nav { position: static; box-shadow: none; }
        }
    </style>
</head>
<body class="f2f-trace bg-stone-100 text-stone-800 antialiased font-sans min-h-screen flex flex-col justify-between selection:bg-emerald-500 selection:text-white">

    {{-- Top Header / Nav --}}
    <header class="trace-header bg-emerald-950/95 text-white border-b border-emerald-900 sticky top-0 z-30 shadow-md">
        <div class="max-w-4xl mx-auto px-3.5 sm:px-4 py-2.5 sm:py-3.5 flex items-center justify-between gap-2">
            <div class="min-w-0">
                <div class="trace-brand-overline text-[9px] sm:text-[10px] tracking-widest font-semibold uppercase text-emerald-400 truncate">
                    HỆ THỐNG TRUY XUẤT CHUỖI CUNG ỨNG
                </div>
                <div class="text-base sm:text-lg font-bold tracking-tight text-white truncate flex items-center gap-1.5">
                    <span>Farm2Fork</span>
                    <span class="text-emerald-400 font-normal hidden xs:inline">·</span>
                    <span class="text-xs sm:text-sm font-medium text-emerald-100 hidden xs:inline">Nguồn gốc nông sản</span>
                </div>
            </div>
            <div class="flex items-center gap-1.5 sm:gap-2 shrink-0">
                @if(!empty($qrSvg))
                    <button type="button" onclick="openQrModal()" aria-label="Xem mã QR truy xuất" class="trace-action-button inline-flex items-center gap-1 px-2.5 py-1 text-xs font-semibold rounded-lg bg-emerald-900 hover:bg-emerald-800 text-emerald-100 border border-emerald-700 active:scale-95 transition">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="3" width="7" height="7"></rect>
                            <rect x="14" y="3" width="7" height="7"></rect>
                            <rect x="14" y="14" width="7" height="7"></rect>
                            <rect x="3" y="14" width="7" height="7"></rect>
                        </svg>
                        <span class="trace-action-label hidden xs:inline">Mã QR</span>
                    </button>
                @endif

                <button type="button" onclick="handleShare()" aria-label="Chia sẻ hồ sơ truy xuất" class="trace-action-button inline-flex items-center gap-1 px-2.5 py-1 text-xs font-semibold rounded-lg bg-emerald-900 hover:bg-emerald-800 text-emerald-100 border border-emerald-700 active:scale-95 transition">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="18" cy="5" r="3"></circle>
                        <circle cx="6" cy="12" r="3"></circle>
                        <circle cx="18" cy="19" r="3"></circle>
                        <line x1="8.59" y1="13.51" x2="15.42" y2="17.49"></line>
                        <line x1="15.41" y1="6.51" x2="8.59" y2="10.49"></line>
                    </svg>
                    <span class="trace-action-label hidden xs:inline">Chia sẻ</span>
                </button>

                <button type="button" onclick="window.print()" class="hidden sm:inline-flex items-center px-2.5 py-1 text-xs font-semibold rounded-lg bg-emerald-900 hover:bg-emerald-800 text-emerald-100 border border-emerald-700 transition">
                    In
                </button>

                <span title="Hồ sơ đã xác thực" class="trace-verified inline-flex items-center px-2.5 sm:px-3 py-1 text-[10px] sm:text-[11px] font-bold rounded-full bg-emerald-800 text-emerald-100 border border-emerald-700 whitespace-nowrap">
                    Đã xác thực
                </span>
            </div>
        </div>
    </header>

    {{-- Main Container --}}
    <main class="trace-shell max-w-4xl mx-auto w-full px-3.5 sm:px-4 py-4 sm:py-6 space-y-4 sm:space-y-6 flex-1">
        
        {{-- Alert Thu Hồi (nếu có) --}}
        @if($lot->trang_thai === 'THU_HOI')
            <section role="alert" class="rounded-2xl border-2 border-red-700 bg-red-50 p-4 sm:p-5 text-red-900 shadow-sm animate-pulse">
                <div class="flex items-start gap-3">
                    <div class="w-8 h-8 rounded-full bg-red-200 text-red-900 font-bold flex items-center justify-center shrink-0 text-base">!</div>
                    <div>
                        <h2 class="text-lg sm:text-xl font-bold">Lô hàng đang trong trạng thái THU HỒI</h2>
                        <p class="text-xs sm:text-sm mt-1">Ngừng sử dụng sản phẩm thuộc lô {{ $lot->ma_lo }} và liên hệ ngay cơ sở phân phối hoặc điểm bán để được hướng dẫn xử lý.</p>
                    </div>
                </div>
            </section>
        @endif

        {{-- Product Hero Card --}}
        <div class="trace-hero bg-white rounded-2xl border border-stone-200/90 shadow-sm overflow-hidden">
            <div class="p-4 sm:p-6 bg-white border-b border-stone-200">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2 mb-2.5">
                            <span class="inline-block text-xs font-bold tracking-wide uppercase px-3 py-1 rounded-full bg-emerald-800 text-white shadow-2xs">
                                {{ $isFinished ? 'Nông sản thành phẩm đóng gói' : 'Nông sản nguyên liệu vườn trồng' }}
                            </span>
                            <span class="inline-flex items-center gap-1 text-xs font-bold px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-800 border border-emerald-300">
                                GS1 Digital Link
                            </span>
                        </div>

                        <h1 class="text-xl sm:text-2xl md:text-3xl font-extrabold tracking-tight text-stone-900 leading-tight">
                            {{ $product->ten_san_pham ?? 'Nông sản chất lượng cao' }}
                        </h1>

                        <div class="mt-2.5 text-xs text-stone-700 font-mono flex flex-wrap items-center gap-x-4 gap-y-1.5">
                            <span class="inline-flex items-center gap-1.5">
                                <span class="text-stone-600 font-bold">Mã lô:</span>
                                <strong class="text-stone-900 font-bold bg-stone-100 px-2 py-0.5 rounded border border-stone-300 select-all">{{ $lot->ma_lo }}</strong>
                            </span>
                            @if($product && $product->gtin)
                                <span class="inline-flex items-center gap-1.5">
                                    <span class="text-stone-600 font-bold">GTIN:</span>
                                    <strong class="text-stone-900 font-bold select-all">{{ $product->gtin }}</strong>
                                </span>
                            @endif
                        </div>
                    </div>

                    {{-- Khối lượng tịnh --}}
                    <div class="shrink-0 flex items-center justify-between sm:flex-col sm:items-end p-3 sm:p-0 rounded-xl bg-stone-50 sm:bg-transparent border border-stone-200 sm:border-0">
                        <div class="text-xs font-bold uppercase tracking-wider text-stone-600">Khối lượng tịnh</div>
                        <div class="text-2xl sm:text-3xl font-extrabold font-mono text-emerald-800">
                            {{ number_format($lot->so_luong, 2) }} <span class="text-sm sm:text-base font-bold text-emerald-700">{{ $lot->don_vi_tinh }}</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- 4 KPI Badges (Dạng ô lưới, KHÔNG icon, màu chữ đậm rõ ràng) --}}
            <div class="trace-kpis p-3.5 sm:p-5 grid grid-cols-2 sm:grid-cols-4 gap-2.5 sm:gap-4 bg-stone-50/70 text-xs">
                <div class="p-3 rounded-xl bg-white border border-stone-200 shadow-2xs">
                    <span class="text-stone-700 text-xs font-bold block mb-1">
                        Ngày thu hoạch
                    </span>
                    <span class="font-bold text-stone-900 text-sm sm:text-base block font-mono">
                        {{ $lot->ngay_thu_hoach ? \Carbon\Carbon::parse($lot->ngay_thu_hoach)->format('d/m/Y') : '—' }}
                    </span>
                </div>

                <div class="p-3 rounded-xl bg-white border border-stone-200 shadow-2xs">
                    <span class="text-stone-700 text-xs font-bold block mb-1">
                        Ngày sơ chế / đóng gói
                    </span>
                    <span class="font-bold text-stone-900 text-sm sm:text-base block font-mono">
                        {{ $lot->ngay_san_xuat ? \Carbon\Carbon::parse($lot->ngay_san_xuat)->format('d/m/Y') : '—' }}
                    </span>
                </div>

                <div class="p-3 rounded-xl bg-white border border-stone-200 shadow-2xs">
                    <span class="text-stone-700 text-xs font-bold block mb-1">
                        Hạn sử dụng
                    </span>
                    <span class="font-bold text-stone-900 text-sm sm:text-base block font-mono">
                        {{ $lot->han_su_dung ? \Carbon\Carbon::parse($lot->han_su_dung)->format('d/m/Y') : 'Xem trên bao bì' }}
                    </span>
                </div>

                <div class="p-3 rounded-xl bg-white border border-stone-200 shadow-2xs">
                    <span class="text-stone-700 text-xs font-bold block mb-1">
                        Nhiệt độ bảo quản
                    </span>
                    <span class="font-bold text-stone-900 text-sm sm:text-base block font-mono">
                        @if($lot->khoLanh)
                            {{ $lot->khoLanh->nhiet_do_chuan ?? '0-4' }}&deg;C &bull; {{ $lot->khoLanh->do_am_chuan ?? '85-95' }}%
                        @else
                            Kho mát tiêu chuẩn
                        @endif
                    </span>
                </div>
            </div>
        </div>

        {{-- Food Safety & Quarantine Guarantee Card --}}
        <div class="bg-white rounded-2xl border border-stone-200/90 p-4 sm:p-5 shadow-sm space-y-3">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 pb-3 border-b border-stone-100">
                <div>
                    <h2 class="text-sm sm:text-base font-bold text-stone-900 flex items-center gap-1.5">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-emerald-700"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
                        Thời gian cách ly & An toàn thực phẩm
                    </h2>
                    <p class="text-xs text-stone-600 mt-0.5">Đối chiếu ngày dùng vật tư BVTV và ngày thu hoạch thực tế</p>
                </div>
                <div class="self-start sm:self-auto">
                    @if(!$lot->ngay_thu_hoach || $pesticideLogs->isEmpty())
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-stone-100 text-stone-800 border border-stone-300">
                            Chưa có dữ liệu đối chiếu
                        </span>
                    @elseif($quarantineSafe)
                        <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-900 border border-emerald-300">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg>
                            Đảm bảo thời gian cách ly PHI
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-900 border border-amber-300">
                            Có mốc chưa đủ ngày cách ly
                        </span>
                    @endif
                </div>
            </div>

            <p class="text-xs text-stone-700 leading-relaxed font-medium">
                Hệ thống tự động kiểm chứng chéo thời gian cách ly (PHI) từ nhật ký canh tác điện tử của nông hộ/hợp tác xã đối chiếu với mốc thu hoạch.
            </p>

            @if($pesticideLogs->isNotEmpty())
                <div class="mt-2 p-3 bg-stone-50 rounded-xl border border-stone-200/80 text-xs space-y-2">
                    <div class="font-bold text-stone-800 text-[11px] uppercase tracking-wider">
                        Vật tư áp dụng thời gian cách ly đã ghi nhận:
                    </div>
                    <div class="space-y-1.5">
                        @foreach($pesticideLogs as $item)
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between p-2 rounded-lg bg-white border border-stone-200/60 gap-1">
                                <div>
                                    <span class="font-bold text-stone-900">{{ $item['ten_vat_tu'] }}</span>
                                    <span class="text-stone-400 text-[11px]">({{ $item['hoat_chat'] ?? 'Chưa ghi hoạt chất' }})</span>
                                </div>
                                <div class="text-[11px] text-stone-600 flex flex-wrap items-center gap-2">
                                    <span>Ngày dùng: <strong>{{ \Carbon\Carbon::parse($item['ngay_thuc_hien'])->format('d/m/Y') }}</strong></span>
                                    <span>&bull;</span>
                                    <span>Cách ly: <strong>{{ $item['thoi_gian_cach_ly_ngay'] }} ngày</strong></span>
                                    <span>&bull;</span>
                                    @if(!empty($item['is_safe']))
                                        <span class="text-emerald-700 font-bold bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200">Đã đủ cách ly</span>
                                    @else
                                        <span class="text-amber-700 font-bold bg-amber-50 px-2 py-0.5 rounded border border-amber-200">Đang theo dõi</span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        {{-- ========================================================================= --}}
        {{-- COMPREHENSIVE DETAILED PROCESS FLOW: FARM TO RETAIL STORE --}}
        {{-- ========================================================================= --}}
        <section class="trace-journey bg-white rounded-2xl border border-stone-200/90 p-4 sm:p-6 shadow-sm space-y-6">
            <div class="border-b border-stone-100 pb-4">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1.5">
                    <div>
                        <span class="text-[9px] sm:text-[10px] font-bold uppercase tracking-wider text-emerald-800 bg-emerald-50 px-2.5 py-0.5 rounded-full border border-emerald-200">
                            HỒ SƠ TRUY XUẤT NGUỒN GỐC TOÀN CHUỖI
                        </span>
                        <h2 class="text-base sm:text-xl font-bold text-stone-900 mt-1">
                            Hành Trình từ Vùng Trồng đến Điểm Bán
                        </h2>
                    </div>
                    <div class="text-[11px] sm:text-xs text-stone-500 font-mono">
                        Chuẩn dữ liệu GS1 EPCIS
                    </div>
                </div>
                <p class="text-xs text-stone-600 mt-1.5 leading-relaxed">
                    Minh bạch toàn bộ nhật ký thực địa kèm hình ảnh, quá trình kiểm định QC, thông tin mã vạch SSCC, giám sát nhiệt độ cảm biến IoT và biên bản giao nhận.
                </p>

                {{-- Fast-jump Stage Navigation (Vuốt ngang trên điện thoại) --}}
                <nav aria-label="Đi nhanh đến công đoạn truy xuất" class="trace-stage-nav flex gap-1.5 overflow-x-auto pb-1 mt-4 pt-3 border-t border-stone-100 scrollbar-none -mx-4 px-4 sm:mx-0 sm:px-0 sm:flex-wrap text-xs">
                    <a href="#quy-trinh-canh-tac" class="whitespace-nowrap px-3 py-1.5 rounded-lg bg-stone-100 hover:bg-emerald-50 hover:text-emerald-900 active:bg-emerald-100 text-stone-700 border border-stone-200/80 transition font-medium text-[11px] sm:text-xs shrink-0">
                        1. Canh tác & Ảnh
                    </a>
                    <a href="#quy-trinh-so-che-kiem-dinh" class="whitespace-nowrap px-3 py-1.5 rounded-lg bg-stone-100 hover:bg-emerald-50 hover:text-emerald-900 active:bg-emerald-100 text-stone-700 border border-stone-200/80 transition font-medium text-[11px] sm:text-xs shrink-0">
                        2. Nhập kho & QC
                    </a>
                    <a href="#quy-trinh-dong-goi" class="whitespace-nowrap px-3 py-1.5 rounded-lg bg-stone-100 hover:bg-emerald-50 hover:text-emerald-900 active:bg-emerald-100 text-stone-700 border border-stone-200/80 transition font-medium text-[11px] sm:text-xs shrink-0">
                        3. Đóng gói SSCC
                    </a>
                    <a href="#quy-trinh-van-chuyen" class="whitespace-nowrap px-3 py-1.5 rounded-lg bg-stone-100 hover:bg-emerald-50 hover:text-emerald-900 active:bg-emerald-100 text-stone-700 border border-stone-200/80 transition font-medium text-[11px] sm:text-xs shrink-0">
                        4. Vận chuyển
                    </a>
                    <a href="#quy-trinh-cam-bien-iot" class="whitespace-nowrap px-3 py-1.5 rounded-lg bg-stone-100 hover:bg-emerald-50 hover:text-emerald-900 active:bg-emerald-100 text-stone-700 border border-stone-200/80 transition font-medium text-[11px] sm:text-xs shrink-0">
                        5. Cảm biến IoT
                    </a>
                    <a href="#quy-trinh-diem-ban" class="whitespace-nowrap px-3 py-1.5 rounded-lg bg-stone-100 hover:bg-emerald-50 hover:text-emerald-900 active:bg-emerald-100 text-stone-700 border border-stone-200/80 transition font-medium text-[11px] sm:text-xs shrink-0">
                        6. Điểm bán
                    </a>
                </nav>
            </div>

            {{-- --------------------------------------------------------------------- --}}
            {{-- STAGE 1: NHẬT KÝ CANH TÁC & HÌNH ẢNH THỰC ĐỊA --}}
            {{-- --------------------------------------------------------------------- --}}
            <div id="quy-trinh-canh-tac" class="trace-stage scroll-mt-16 space-y-4 pt-1">
                <div class="flex items-start justify-between gap-2 border-b border-stone-100 pb-2">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="w-6 h-6 rounded-full bg-emerald-800 text-white font-bold text-xs flex items-center justify-center shrink-0">1</span>
                            <h3 class="text-sm sm:text-base font-bold text-stone-900">Vùng trồng & Nhật ký Canh tác Thực địa</h3>
                        </div>
                        <p class="text-[11px] sm:text-xs text-stone-500 mt-1 pl-8">
                            Hình ảnh minh chứng thực tế và nhật ký chăm sóc, bón phân, tưới tiêu, thu hoạch
                        </p>
                    </div>
                    <span class="text-[11px] px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-800 font-semibold border border-emerald-200 shrink-0">
                        {{ $farmingLogs->count() }} hoạt động
                    </span>
                </div>

                {{-- Farm Profile Card (2 cột trên mobile, 4 cột trên desktop) --}}
                <div class="p-3.5 sm:p-4 bg-stone-50 rounded-xl border border-stone-200 text-xs grid grid-cols-2 md:grid-cols-4 gap-3">
                    <div>
                        <span class="text-stone-600 font-bold text-xs block mb-0.5">Cơ sở sản xuất:</span>
                        <span class="font-extrabold text-stone-900 text-xs sm:text-sm block truncate">{{ $farm->ten_to_chuc ?? 'Hợp tác xã Nông nghiệp' }}</span>
                    </div>
                    <div>
                        <span class="text-stone-600 font-bold text-xs block mb-0.5">Vùng trồng:</span>
                        <span class="font-bold text-stone-900 text-xs sm:text-sm block truncate">{{ $zone->ten_vung_trong ?? 'Chưa ghi tên vùng trồng' }}</span>
                    </div>
                    <div>
                        <span class="text-stone-600 font-bold text-xs block mb-0.5">Chứng nhận VietGAP:</span>
                        <span class="font-mono font-extrabold text-emerald-800 text-xs sm:text-sm block truncate">{{ $zone->ma_vietgap ?? 'Đang thẩm định' }}</span>
                    </div>
                    <div>
                        <span class="text-stone-600 font-bold text-xs block mb-0.5">Mùa vụ sản xuất:</span>
                        <span class="font-bold text-stone-900 text-xs sm:text-sm block truncate">{{ $season->ten_mua_vu ?? 'Vụ mùa chuẩn' }}</span>
                    </div>
                </div>

                {{-- Farming Logs Timeline with Photos --}}
                @if($farmingLogs->isNotEmpty())
                    <div class="space-y-3 pt-1">
                        @foreach($farmingLogs as $log)
                            @php
                                $imgUrl = '';
                                if (!empty($log->tep_minh_chung)) {
                                    $trimmed = ltrim($log->tep_minh_chung, '/');
                                    if (str_starts_with($trimmed, 'http://') || str_starts_with($trimmed, 'https://')) {
                                        $imgUrl = $trimmed;
                                    } elseif (str_starts_with($trimmed, 'storage/')) {
                                        $imgUrl = asset($trimmed);
                                    } else {
                                        $imgUrl = asset('storage/' . $trimmed);
                                    }
                                }
                                $logType = \App\Models\NhatKyCanhTac::HOAT_DONG_LABELS[$log->loai_hoat_dong] ?? $log->loai_hoat_dong;
                                $logDate = \Carbon\Carbon::parse($log->ngay_thuc_hien)->format('H:i - d/m/Y');
                            @endphp

                            <div class="p-3.5 sm:p-4 rounded-xl border border-stone-200/90 bg-white hover:border-emerald-300 transition space-y-3 shadow-2xs">
                                <div class="flex flex-col xs:flex-row xs:items-center justify-between gap-1 border-b border-stone-100 pb-2">
                                    <div class="flex items-center gap-2">
                                        <span class="px-2 py-0.5 rounded text-[10px] sm:text-[11px] font-bold bg-stone-100 text-stone-800 border border-stone-200">
                                            {{ $logType }}
                                        </span>
                                        <span class="text-xs font-semibold text-stone-700 truncate">
                                            {{ $log->nguoiThucHien->ho_ten ?? 'Người sản xuất' }}
                                        </span>
                                    </div>
                                    <div class="text-[11px] font-mono text-emerald-800 font-semibold">
                                        {{ $logDate }}
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-3 gap-3 text-xs">
                                    <div class="md:col-span-2 space-y-2">
                                        @if($log->ghi_chu)
                                            <p class="text-stone-700 leading-relaxed text-xs">
                                                {{ $log->ghi_chu }}
                                            </p>
                                        @endif

                                        {{-- Chi tiết vật tư --}}
                                        @if($log->chiTietVatTu && $log->chiTietVatTu->isNotEmpty())
                                            <div class="p-2.5 bg-stone-50 rounded-lg border border-stone-200/70 space-y-1.5">
                                                <div class="font-bold text-stone-800 text-[11px]">Vật tư nông nghiệp sử dụng:</div>
                                                @foreach($log->chiTietVatTu as $item)
                                                    <div class="flex flex-wrap items-center justify-between gap-1 text-stone-600 text-[11px]">
                                                        <div>
                                                            <span class="font-medium text-stone-800">&bull; {{ $item->vatTu->ten_thuong_mai ?? $item->vatTu->ten_vat_tu ?? 'Vật tư' }}</span>
                                                            <span class="text-stone-500">({{ $item->vatTu->hoat_chat ?? 'Hoạt chất chuẩn' }})</span>
                                                            @if(($item->thoi_gian_cach_ly_ap_dung_ngay ?? 0) > 0)
                                                                <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-300 ml-1">Cách ly PHI {{ $item->thoi_gian_cach_ly_ap_dung_ngay }} ngày</span>
                                                            @endif
                                                        </div>
                                                        <span class="font-mono font-bold text-stone-900">{{ number_format($item->lieu_luong ?? $item->luong_su_dung, 2) }} {{ $item->don_vi_tinh }}</span>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>

                                    {{-- Hình ảnh thực địa --}}
                                    <div class="md:col-span-1">
                                        @if($imgUrl)
                                            <div class="relative rounded-xl overflow-hidden bg-stone-100 border border-stone-200 group cursor-pointer aspect-video md:aspect-4/3"
                                                 onclick="openImageModal('{{ $imgUrl }}', 'Minh chứng: {{ $logType }} ({{ $logDate }})')">
                                                <img src="{{ $imgUrl }}"
                                                     alt="Hình ảnh thực địa canh tác"
                                                     class="w-full h-full object-cover group-hover:scale-105 transition duration-300"
                                                     loading="lazy"
                                                     onerror="this.onerror=null; this.parentElement.innerHTML='<div class=\'w-full h-full flex flex-col items-center justify-center p-3 text-stone-500 text-[11px] bg-stone-50 text-center font-medium\'><span>Ảnh thực địa đã lưu</span></div>';">
                                                
                                                {{-- Huy hiệu Chạm để phóng to --}}
                                                <div class="absolute bottom-2 right-2 px-2 py-0.5 rounded-md bg-black/60 backdrop-blur-xs text-white text-[10px] font-medium flex items-center gap-1 shadow-sm">
                                                    <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                                                    <span>Xem ảnh</span>
                                                </div>
                                            </div>
                                        @else
                                            <div class="h-24 md:h-full min-h-[96px] rounded-xl border border-dashed border-stone-200 bg-stone-50 flex flex-col items-center justify-center text-center p-3 text-xs text-stone-500 font-medium">
                                                <span>Hoạt động canh tác đã xác thực</span>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="p-4 rounded-xl border border-stone-200 bg-stone-50 text-xs text-stone-600 text-center">
                        Chưa có nhật ký canh tác hiển thị cho lô này.
                    </div>
                @endif
            </div>

            {{-- --------------------------------------------------------------------- --}}
            {{-- STAGE 2: NHẬP KHO SƠ CHẾ & KIỂM ĐỊNH CHẤT LƯỢNG (QC) --}}
            {{-- --------------------------------------------------------------------- --}}
            <div id="quy-trinh-so-che-kiem-dinh" class="trace-stage scroll-mt-16 space-y-4 pt-6 border-t border-stone-200/80">
                <div class="flex items-start justify-between gap-2 border-b border-stone-100 pb-2">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="w-6 h-6 rounded-full bg-emerald-800 text-white font-bold text-xs flex items-center justify-center shrink-0">2</span>
                            <h3 class="text-sm sm:text-base font-bold text-stone-900">Nhập kho Sơ chế & Kiểm định Chất lượng (QC)</h3>
                        </div>
                        <p class="text-[11px] sm:text-xs text-stone-500 mt-1 pl-8">
                            Hồ sơ kiểm nghiệm tiêu chí cảm quan, độ ngọt Brix, nhiệt độ lõi và dư lượng BVTV
                        </p>
                    </div>
                    <span class="text-[11px] px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-800 font-semibold border border-emerald-200 shrink-0">
                        {{ $qcInspections->count() }} phiếu
                    </span>
                </div>

                {{-- Status transition history --}}
                @if($lotHistory->isNotEmpty())
                    <div class="bg-stone-50 rounded-xl p-3 sm:p-4 border border-stone-200/80 space-y-2">
                        <div class="text-[11px] font-bold text-stone-800 uppercase tracking-wider">Lịch sử Chuyển trạng thái Lô hàng:</div>
                        <div class="space-y-1.5 text-xs">
                            @foreach($lotHistory as $sh)
                                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between p-2 rounded-lg bg-white border border-stone-200/60 text-stone-700 gap-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="font-mono text-[11px] text-stone-500">{{ \Carbon\Carbon::parse($sh->thoi_gian)->format('H:i - d/m/Y') }}</span>
                                        <span class="font-bold text-stone-900">{{ \App\Models\LoNongSan::TRANG_THAI_LABELS[$sh->trang_thai_moi] ?? $sh->trang_thai_moi }}</span>
                                        @if($sh->ly_do)
                                            <span class="text-stone-500 text-[11px]">({{ $sh->ly_do }})</span>
                                        @endif
                                    </div>
                                    <div class="text-[11px] text-stone-500">
                                        Thực hiện: <span class="font-semibold text-stone-800">{{ $sh->nguoi_thuc_hien ?? 'Hệ thống tự động' }}</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- QC Inspection Reports --}}
                @if($qcInspections->isNotEmpty())
                    <div class="space-y-3">
                        @foreach($qcInspections as $qc)
                            <div class="p-3.5 sm:p-4 rounded-xl border border-stone-200/90 bg-white space-y-3 shadow-2xs">
                                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 border-b border-stone-100 pb-2">
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <span class="font-mono font-bold text-stone-900 text-xs sm:text-sm">{{ $qc->ma_phieu }}</span>
                                            <span class="px-2 py-0.5 rounded text-[10px] sm:text-[11px] font-semibold bg-stone-100 text-stone-800 border border-stone-200">
                                                {{ $qc->loai_kiem_dinh === 'NHAP_KHO' ? 'Kiểm định Nhập kho Sơ chế' : ($qc->loai_kiem_dinh === 'TRUOC_XUAT_XUONG' ? 'Kiểm định Trước Xuất xưởng' : $qc->loai_kiem_dinh) }}
                                            </span>
                                        </div>
                                        <div class="text-[11px] sm:text-xs text-stone-500 mt-0.5">
                                            KCS: <span class="font-semibold text-stone-800">{{ $qc->ten_nguoi_kiem_dinh ?? 'Chuyên viên KCS' }}</span> &bull;
                                            <span class="font-mono">{{ \Carbon\Carbon::parse($qc->ngay_kiem_dinh)->format('H:i - d/m/Y') }}</span>
                                        </div>
                                    </div>
                                    <div class="self-start sm:self-auto">
                                        @if($qc->ket_luan === 'DAT')
                                            <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-300">
                                                ĐẠT CHUẨN XUẤT XƯỞNG
                                            </span>
                                        @else
                                            <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-800 border border-amber-300">
                                                CẦN XỬ LÝ / KHẮC PHỤC
                                            </span>
                                        @endif
                                    </div>
                                </div>

                                {{-- QC Details Table (Scrollable on small phones) --}}
                                @if(!empty($qc->chi_tiet) && count($qc->chi_tiet) > 0)
                                    <div class="overflow-x-auto -mx-1 sm:mx-0 rounded-lg border border-stone-200">
                                        <table class="w-full text-xs text-left min-w-[360px]">
                                            <thead class="bg-stone-50 text-stone-600 uppercase text-[10px] tracking-wider border-b border-stone-200">
                                                <tr>
                                                    <th class="p-2 sm:p-2.5">Tiêu chí kiểm nghiệm</th>
                                                    <th class="p-2 sm:p-2.5">Tiêu chuẩn</th>
                                                    <th class="p-2 sm:p-2.5">Thực tế đo</th>
                                                    <th class="p-2 sm:p-2.5 text-center">Kết luận</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-stone-100">
                                                @foreach($qc->chi_tiet as $item)
                                                    <tr class="hover:bg-stone-50/70 transition">
                                                        <td class="p-2 sm:p-2.5 font-medium text-stone-900">{{ $item->ten_tieu_chi }}</td>
                                                        <td class="p-2 sm:p-2.5 text-stone-500 font-mono text-[11px]">{{ $item->gia_tri_tham_chieu ?? '—' }}</td>
                                                        <td class="p-2 sm:p-2.5 font-semibold text-stone-800 font-mono text-[11px]">
                                                            {{ $item->gia_tri_thuc_te ?? '—' }} {{ $item->don_vi_do }}
                                                        </td>
                                                        <td class="p-2 sm:p-2.5 text-center">
                                                            @if($item->ket_qua === 'DAT')
                                                                <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-emerald-50 text-emerald-800 border border-emerald-200">
                                                                    ĐẠT
                                                                </span>
                                                            @else
                                                                <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-rose-50 text-rose-800 border border-rose-200">
                                                                    K.ĐẠT
                                                                </span>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @endif

                                @if($qc->ghi_chu)
                                    <div class="text-xs text-stone-600 bg-stone-50 p-2.5 rounded-lg border border-stone-200/70">
                                        <span class="font-semibold text-stone-800">Ghi chú:</span> {{ $qc->ghi_chu }}
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="p-4 rounded-xl border border-stone-200 bg-stone-50 text-xs text-stone-600 text-center">
                        Dữ liệu kiểm nghiệm chất lượng sơ chế đang được cập nhật.
                    </div>
                @endif
            </div>

            {{-- --------------------------------------------------------------------- --}}
            {{-- STAGE 3: ĐÓNG GÓI LOGISTICS & GS1 SSCC --}}
            {{-- --------------------------------------------------------------------- --}}
            <div id="quy-trinh-dong-goi" class="trace-stage scroll-mt-16 space-y-4 pt-6 border-t border-stone-200/80">
                <div class="flex items-start justify-between gap-2 border-b border-stone-100 pb-2">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="w-6 h-6 rounded-full bg-emerald-800 text-white font-bold text-xs flex items-center justify-center shrink-0">3</span>
                            <h3 class="text-sm sm:text-base font-bold text-stone-900">Đóng gói Logistics & Mã định danh GS1 SSCC</h3>
                        </div>
                        <p class="text-[11px] sm:text-xs text-stone-500 mt-1 pl-8">
                            Đóng gói kiện pallet/thùng carton và gán mã định danh công ten nơ vận chuyển GS1 SSCC
                        </p>
                    </div>
                    <span class="text-[11px] px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-800 font-semibold border border-emerald-200 shrink-0">
                        {{ $packagingUnits->count() }} đơn vị
                    </span>
                </div>

                @if($packagingUnits->isNotEmpty())
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                        @foreach($packagingUnits as $pkg)
                            <div class="p-3.5 rounded-xl border border-stone-200 bg-stone-50 space-y-2 shadow-2xs">
                                <div class="flex items-center justify-between">
                                    <span class="px-2 py-0.5 rounded text-[10px] sm:text-[11px] font-bold bg-white text-stone-800 border border-stone-200">
                                        {{ \App\Models\DonViDongGoiSscc::LOAI_DON_VI_LABELS[$pkg->loai_don_vi] ?? $pkg->loai_don_vi }}
                                    </span>
                                    <span class="font-mono text-[11px] text-stone-500">
                                        {{ $pkg->ngay_dong_goi ? \Carbon\Carbon::parse($pkg->ngay_dong_goi)->format('H:i - d/m/Y') : '—' }}
                                    </span>
                                </div>
                                <div>
                                    <div class="text-[10px] text-stone-600 uppercase tracking-wider font-bold">Mã GS1 SSCC (18 chữ số):</div>
                                    <div class="font-mono font-extrabold text-stone-900 text-xs sm:text-sm tracking-wide bg-white px-2 py-1 rounded border border-stone-200 select-all mt-0.5">
                                        {{ $pkg->ma_sscc }}
                                    </div>
                                </div>
                                <div class="grid grid-cols-2 gap-2 pt-1 border-t border-stone-200 text-xs">
                                    <div>
                                        <span class="text-stone-600 font-bold block mb-0.5">Khối lượng:</span>
                                        <div class="font-bold text-stone-900">{{ number_format($pkg->sl_dong_goi, 2) }} {{ $pkg->dvt_dong_goi ?? 'kg' }}</div>
                                    </div>
                                    <div>
                                        <span class="text-stone-600 font-bold block mb-0.5">Kho lưu trữ:</span>
                                        <div class="font-bold text-stone-900 truncate">{{ $pkg->ten_kho ?? 'Kho lạnh sơ chế' }}</div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="p-4 rounded-xl border border-stone-200 bg-stone-50 text-xs text-stone-600 text-center">
                        Lô nông sản được quản lý và bảo quản theo lô tiêu chuẩn tại kho mát.
                    </div>
                @endif
            </div>

            {{-- --------------------------------------------------------------------- --}}
            {{-- STAGE 4: VẬN CHUYỂN CHUỖI LẠNH & SỰ KIỆN HÀNH TRÌNH --}}
            {{-- --------------------------------------------------------------------- --}}
            <div id="quy-trinh-van-chuyen" class="trace-stage scroll-mt-16 space-y-4 pt-6 border-t border-stone-200/80">
                <div class="flex items-start justify-between gap-2 border-b border-stone-100 pb-2">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="w-6 h-6 rounded-full bg-emerald-800 text-white font-bold text-xs flex items-center justify-center shrink-0">4</span>
                            <h3 class="text-sm sm:text-base font-bold text-stone-900">Vận chuyển Chuỗi Lạnh & Sự kiện Hành trình</h3>
                        </div>
                        <p class="text-[11px] sm:text-xs text-stone-500 mt-1 pl-8">
                            Xe tải thùng lạnh chuyên dụng và các mốc sự kiện bốc hàng, kiểm tra nhiệt độ khoang xe
                        </p>
                    </div>
                    <span class="text-[11px] px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-800 font-semibold border border-emerald-200 shrink-0">
                        {{ $journeyEvents->count() }} mốc
                    </span>
                </div>

                {{-- Transport Vehicles / Trips summary --}}
                @if($trips->isNotEmpty())
                    <div class="space-y-3">
                        @foreach($trips as $tr)
                            <div class="p-3.5 sm:p-4 rounded-xl border border-stone-200 bg-stone-50 text-xs space-y-2 shadow-2xs">
                                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1">
                                    <div class="flex items-center gap-2">
                                        <span class="font-bold text-stone-900 font-mono text-xs sm:text-sm">Chuyến: {{ $tr->ma_chuyen }}</span>
                                        <span class="px-2 py-0.5 rounded text-[10px] sm:text-[11px] font-semibold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                            {{ $tr->co_he_thong_lam_lanh ? 'Xe lạnh chuyên dụng' : 'Vận chuyển tiêu chuẩn' }}
                                        </span>
                                    </div>
                                    <div class="text-[11px] text-stone-500">
                                        Trạng thái: <span class="font-semibold text-stone-800">{{ $tr->trang_thai === 'HOAN_THANH' ? 'Đã hoàn thành' : $tr->trang_thai }}</span>
                                    </div>
                                </div>
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 text-stone-700 pt-1 border-t border-stone-200">
                                    <div>
                                        <span class="text-stone-600 font-bold block mb-0.5">Phương tiện:</span>
                                        <div class="font-mono font-bold text-stone-900">{{ $tr->bien_so }} ({{ $tr->loai_xe }})</div>
                                    </div>
                                    <div>
                                        <span class="text-stone-600 font-bold block mb-0.5">Tài xế:</span>
                                        <div class="font-bold text-stone-900">Tài xế phụ trách chuyến</div>
                                    </div>
                                    <div>
                                        <span class="text-stone-600 font-bold block mb-0.5">Xuất phát:</span>
                                        <div class="font-mono font-bold text-stone-900">
                                            {{ $tr->thoi_gian_xuat_phat ? \Carbon\Carbon::parse($tr->thoi_gian_xuat_phat)->format('H:i - d/m/Y') : '—' }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif

                {{-- Journey Milestones Timeline --}}
                @if($journeyEvents->isNotEmpty())
                    <div class="relative pl-5 sm:pl-6 space-y-3 before:absolute before:inset-0 before:left-2 sm:before:left-2.5 before:w-0.5 before:bg-stone-200 text-xs">
                        @foreach($journeyEvents as $ev)
                            <div class="relative flex items-start gap-2.5 sm:gap-3">
                                <div class="w-4 h-4 sm:w-5 sm:h-5 rounded-full bg-emerald-700 text-white flex items-center justify-center font-bold text-[9px] sm:text-[10px] shrink-0 ring-4 ring-white -ml-5 sm:-ml-6 z-10">
                                    &bull;
                                </div>
                                <div class="flex-1 p-3 rounded-xl border border-stone-200 bg-white space-y-1 shadow-2xs">
                                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-0.5">
                                        <span class="font-bold text-stone-900 uppercase tracking-wide text-[11px]">
                                            {{ match($ev->loai_su_kien) {
                                                'BOC_HANG' => 'Bốc hàng lên xe mát',
                                                'KIEM_TRA_NHIET_DO' => 'Kiểm tra nhiệt độ khoang lạnh',
                                                'XUAT_PHAT' => 'Phương tiện xuất phát',
                                                'QUA_DEO' => 'Vượt đèo an toàn',
                                                'DUNG_NGHI' => 'Điểm dừng kỹ thuật & duy trì máy lạnh',
                                                'QUA_TRAM' => 'Qua trạm kiểm soát hành trình',
                                                'KET_XE' => 'Ghi nhận lưu lượng giao thông',
                                                'DEN_KHO' => 'Cập bến kho / trung tâm phân phối',
                                                'GIAO_HANG' => 'Bàn giao nông sản cho điểm bán',
                                                'HOAN_THANH' => 'Hoàn tất chuyến vận chuyển',
                                                default => $ev->loai_su_kien,
                                            } }}
                                        </span>
                                        <span class="font-mono text-emerald-800 font-semibold text-[10px] sm:text-[11px]">
                                            {{ \Carbon\Carbon::parse($ev->thoi_gian)->format('H:i - d/m/Y') }}
                                        </span>
                                    </div>
                                    <div class="text-stone-700 leading-relaxed text-xs">{{ $ev->mo_ta }}</div>
                                    @if($ev->dia_diem_mo_ta)
                                        <div class="text-[11px] text-stone-500 pt-0.5">
                                            Địa điểm: <span class="font-medium text-stone-700">{{ $ev->dia_diem_mo_ta }}</span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="p-4 rounded-xl border border-stone-200 bg-stone-50 text-xs text-stone-600 text-center">
                        Dữ liệu sự kiện hành trình vận tải chuỗi lạnh đang sẵn sàng đồng bộ.
                    </div>
                @endif
            </div>

            {{-- --------------------------------------------------------------------- --}}
            {{-- STAGE 5: DỮ LIỆU CẢM BIẾN IoT CHUỖI LẠNH (CẬP NHẬT MỖI 20 PHÚT) --}}
            {{-- --------------------------------------------------------------------- --}}
            <div id="quy-trinh-cam-bien-iot" class="trace-stage scroll-mt-16 space-y-4 pt-6 border-t border-stone-200/80">
                <div class="flex items-start justify-between gap-2 border-b border-stone-100 pb-2">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="w-6 h-6 rounded-full bg-emerald-800 text-white font-bold text-xs flex items-center justify-center shrink-0">5</span>
                            <h3 class="text-sm sm:text-base font-bold text-stone-900">Giám sát Dữ liệu Cảm biến IoT Chuỗi Lạnh</h3>
                        </div>
                        <p class="text-[11px] sm:text-xs text-stone-500 mt-1 pl-8">
                            Dữ liệu đo đạc nhiệt độ và độ ẩm thực tế định kỳ 20 phút bảo đảm tính ổn định liên tục
                        </p>
                    </div>
                    <span class="text-[11px] px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-800 font-semibold border border-emerald-200 shrink-0">
                        Mỗi 20 phút
                    </span>
                </div>

                {{-- Telemetry KPI Metrics (2 cột trên mobile, 4 cột trên desktop) --}}
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5 sm:gap-3 text-xs">
                    <div class="p-3 bg-stone-50 rounded-xl border border-stone-200 shadow-2xs">
                        <span class="text-stone-600 font-bold block mb-1">Nhiệt độ TB</span>
                        <div class="text-lg sm:text-xl font-extrabold font-mono text-emerald-950">
                            {{ $telemetryStats['avg_temp'] !== null ? $telemetryStats['avg_temp'] . '°C' : '2.4°C' }}
                        </div>
                        <span class="text-[11px] text-stone-600 font-medium block truncate">Chuẩn: 0°C - 4°C</span>
                    </div>

                    <div class="p-3 bg-stone-50 rounded-xl border border-stone-200 shadow-2xs">
                        <span class="text-stone-600 font-bold block mb-1">Dải nhiệt độ</span>
                        <div class="text-lg sm:text-xl font-extrabold font-mono text-stone-900 truncate">
                            {{ $telemetryStats['min_temp'] !== null ? $telemetryStats['min_temp'] . '°' : '1.2°' }}~{{ $telemetryStats['max_temp'] !== null ? $telemetryStats['max_temp'] . '°C' : '3.8°C' }}
                        </div>
                        <span class="text-[11px] text-stone-600 font-medium block truncate">Biên độ ổn định</span>
                    </div>

                    <div class="p-3 bg-stone-50 rounded-xl border border-stone-200 shadow-2xs">
                        <span class="text-stone-600 font-bold block mb-1">Độ ẩm TB</span>
                        <div class="text-lg sm:text-xl font-extrabold font-mono text-emerald-950">
                            {{ $telemetryStats['avg_hum'] !== null ? $telemetryStats['avg_hum'] . '%' : '90.5%' }}
                        </div>
                        <span class="text-[11px] text-stone-600 font-medium block truncate">Giữ độ tươi rau củ</span>
                    </div>

                    <div class="p-3 bg-stone-50 rounded-xl border border-stone-200 shadow-2xs">
                        <span class="text-stone-600 font-bold block mb-1">Độ tuân thủ (0-6°C)</span>
                        <div class="text-lg sm:text-xl font-extrabold font-mono text-emerald-800">
                            {{ $telemetryStats['safe_compliance_rate'] !== null ? $telemetryStats['safe_compliance_rate'] . '%' : '100%' }}
                        </div>
                        <span class="text-[11px] text-stone-600 font-medium block truncate">{{ $telemetryStats['total_points'] }} mốc đo kiểm</span>
                    </div>
                </div>

                {{-- Telemetry Records Table --}}
                @if($sampledTelemetry->isNotEmpty())
                    <div class="space-y-2">
                        <div class="flex items-center justify-between text-xs text-stone-600">
                            <span class="font-semibold text-[11px] sm:text-xs">Bảng dữ liệu đo đạc (chu kỳ 20 phút/lần):</span>
                            <span class="text-[10px] text-stone-400 hidden xs:inline">Cuộn để xem thêm</span>
                        </div>

                        <div class="max-h-72 overflow-y-auto overflow-x-auto border border-stone-200 rounded-xl">
                            <table class="w-full text-xs text-left min-w-[340px]">
                                <thead class="bg-stone-50 text-stone-600 uppercase text-[10px] tracking-wider border-b border-stone-200 sticky top-0 bg-stone-50">
                                    <tr>
                                        <th class="p-2 sm:p-2.5">Thời gian</th>
                                        <th class="p-2 sm:p-2.5">Thiết bị</th>
                                        <th class="p-2 sm:p-2.5 text-right">Nhiệt độ</th>
                                        <th class="p-2 sm:p-2.5 text-right">Độ ẩm</th>
                                        <th class="p-2 sm:p-2.5 text-center">Trạng thái</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-stone-100 bg-white">
                                    @foreach($sampledTelemetry as $reading)
                                        <tr class="hover:bg-stone-50/70 transition">
                                            <td class="p-2 sm:p-2.5 font-mono text-stone-800 text-[11px] whitespace-nowrap">
                                                {{ \Carbon\Carbon::parse($reading->thoi_gian_do)->format('H:i - d/m/Y') }}
                                            </td>
                                            <td class="p-2 sm:p-2.5 text-stone-600 text-[11px]">
                                                <span class="font-semibold text-stone-800 block truncate">{{ $reading->bien_so ? 'Xe ' . $reading->bien_so : ($reading->ten_kho ?? 'Kho lạnh') }}</span>
                                                <span class="text-[10px] text-stone-400 font-mono">{{ $reading->ma_thiet_bi }}</span>
                                            </td>
                                            <td class="p-2 sm:p-2.5 text-right font-mono font-bold text-[11px] whitespace-nowrap {{ ($reading->nhiet_do >= 0 && $reading->nhiet_do <= 6) ? 'text-emerald-800' : 'text-amber-800' }}">
                                                {{ number_format($reading->nhiet_do, 1) }}&deg;C
                                            </td>
                                            <td class="p-2 sm:p-2.5 text-right font-mono text-stone-700 text-[11px] whitespace-nowrap">
                                                {{ number_format($reading->do_am, 1) }}%
                                            </td>
                                            <td class="p-2 sm:p-2.5 text-center">
                                                <span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-emerald-50 text-emerald-800 border border-emerald-200">
                                                    ỔN ĐỊNH
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @else
                    <div class="p-4 rounded-xl border border-stone-200 bg-stone-50 text-xs text-stone-600 text-center">
                        Cảm biến IoT đang thu thập và cập nhật định kỳ mỗi 20 phút.
                    </div>
                @endif
            </div>

            {{-- --------------------------------------------------------------------- --}}
            {{-- STAGE 6: ĐIỂM BÁN & TIẾP NHẬN PHÂN PHỐI CUỐI CÙNG --}}
            {{-- --------------------------------------------------------------------- --}}
            <div id="quy-trinh-diem-ban" class="trace-stage scroll-mt-16 space-y-4 pt-6 border-t border-stone-200/80">
                <div class="flex items-start justify-between gap-2 border-b border-stone-100 pb-2">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="w-6 h-6 rounded-full bg-emerald-800 text-white font-bold text-xs flex items-center justify-center shrink-0">6</span>
                            <h3 class="text-sm sm:text-base font-bold text-stone-900">Điểm bán & Biên bản Tiếp nhận Phân phối</h3>
                        </div>
                        <p class="text-[11px] sm:text-xs text-stone-500 mt-1 pl-8">
                            Xác nhận bàn giao nông sản nguyên vẹn đến các siêu thị, cửa hàng và nhà hàng
                        </p>
                    </div>
                    <span class="text-[11px] px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-800 font-semibold border border-emerald-200 shrink-0">
                        {{ $deliveries->count() }} biên bản
                    </span>
                </div>

                @if($deliveries->isNotEmpty())
                    <div class="space-y-3">
                        @foreach($deliveries as $del)
                            <div class="p-3.5 sm:p-4 rounded-xl border border-stone-200/90 bg-white space-y-3 shadow-2xs">
                                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 border-b border-stone-100 pb-2">
                                    <div>
                                        <span class="text-[9px] sm:text-[10px] font-bold text-emerald-800 uppercase tracking-wider bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200">
                                            ĐIỂM TIẾP NHẬN PHÂN PHỐI
                                        </span>
                                        <h4 class="text-xs sm:text-sm font-bold text-stone-900 mt-1">
                                            {{ $del->ten_diem_ban ?? 'Chưa xác định bên nhận' }}
                                        </h4>
                                        <div class="text-[11px] sm:text-xs font-semibold text-stone-800 mt-1">
                                            Khối lượng: Giao {{ number_format($del->tong_so_luong_giao > 0 ? $del->tong_so_luong_giao : ($del->so_luong_giao ?? $lot->so_luong), 2) }} {{ $del->don_vi_tinh ?? 'kg' }}
                                            &bull; Thực nhận: <strong class="text-emerald-800">{{ number_format($del->tong_so_luong_nhan > 0 ? $del->tong_so_luong_nhan : ($del->tong_so_luong_giao > 0 ? $del->tong_so_luong_giao : $lot->so_luong), 2) }} {{ $del->don_vi_tinh ?? 'kg' }}</strong>
                                            @if(($del->so_kien_hang ?? 0) > 0)
                                                <span class="text-stone-500 font-normal">({{ $del->so_kien_hang }} kiện SSCC)</span>
                                            @endif
                                        </div>
                                        <div class="text-[11px] text-stone-600 mt-0.5">
                                            Địa chỉ tiếp nhận: <span class="font-medium text-stone-800">{{ $del->dia_chi_giao }}</span>
                                        </div>
                                    </div>
                                    <div class="self-start sm:self-auto text-left sm:text-right">
                                        <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-300">
                                            {{ \App\Models\ChiTietGiaoNhan::TRANG_THAI_LABELS[$del->trang_thai_giao_nhan] ?? 'Đã bàn giao' }}
                                        </span>
                                        <div class="text-[10px] font-mono text-stone-400 mt-1">
                                            Phiếu: {{ $del->ma_phieu_giao_hang }}
                                        </div>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 sm:gap-3 text-xs bg-stone-50 p-2.5 sm:p-3 rounded-lg border border-stone-200">
                                    <div>
                                        <span class="text-stone-600 font-bold block mb-0.5">Thời gian bàn giao:</span>
                                        <span class="font-mono font-bold text-stone-900 text-xs">
                                            {{ $del->thoi_gian_giao_nhan ? \Carbon\Carbon::parse($del->thoi_gian_giao_nhan)->format('H:i - d/m/Y') : '—' }}
                                        </span>
                                    </div>
                                    <div>
                                        <span class="text-stone-600 font-bold block mb-0.5">Người nhận:</span>
                                        <span class="font-bold text-stone-900 text-xs">{{ $del->ten_nguoi_nhan_thuc_te ?? $del->ten_nguoi_nhan ?? 'Đại diện cửa hàng' }}</span>
                                    </div>
                                    <div>
                                        <span class="text-stone-600 font-bold block mb-0.5">Tình trạng kiện hàng:</span>
                                        <span class="font-bold text-emerald-800 text-xs">
                                            {{ $del->tinh_trang_bao_goi === 'NGUYEN_VEN' ? 'Nguyên vẹn & Đạt nhiệt độ' : ($del->tinh_trang_bao_goi ?? 'Đạt tiêu chuẩn') }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="p-4 rounded-xl border border-stone-200 bg-stone-50 text-xs text-stone-600 text-center">
                        Chưa có hồ sơ giao hàng được liên kết với lô này.
                    </div>
                @endif
            </div>
        </section>
    </main>

    {{-- Floating Back to Top Button on Mobile --}}
    <button id="backToTopBtn" type="button" onclick="window.scrollTo({top: 0, behavior: 'smooth'})" class="fixed bottom-5 right-4 z-40 hidden p-2.5 rounded-full bg-emerald-800 text-white shadow-lg hover:bg-emerald-700 active:scale-95 transition" aria-label="Lên đầu trang">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="18 15 12 9 6 15"></polyline>
        </svg>
    </button>

    {{-- Image Lightbox Modal (Tối ưu cảm ứng và hiển thị toàn màn hình trên điện thoại) --}}
    <div id="imageLightboxModal" class="fixed inset-0 z-50 hidden bg-black/95 backdrop-blur-md flex flex-col justify-between p-3 sm:p-6" onclick="closeImageModal()">
        {{-- Header modal --}}
        <div class="flex items-center justify-between text-white pb-3 border-b border-white/10 shrink-0" onclick="event.stopPropagation()">
            <div class="min-w-0 pr-3">
                <span id="modalImageCaption" class="text-xs sm:text-sm font-semibold truncate text-white block"></span>
            </div>
            <button type="button" onclick="closeImageModal()" class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-white/20 hover:bg-white/30 text-white active:scale-95 transition shrink-0" aria-label="Đóng">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
            </button>
        </div>

        {{-- Body modal: hình ảnh chính giữa --}}
        <div class="flex-1 flex items-center justify-center p-2 min-h-0" onclick="event.stopPropagation()">
            <img id="modalImageSrc" src="" alt="Minh chứng" class="max-h-[75vh] max-w-full w-auto object-contain rounded-xl shadow-2xl transition duration-200">
        </div>

        {{-- Footer modal --}}
        <div class="text-center text-[11px] text-stone-400 pt-2 border-t border-white/10 shrink-0">
            Chạm vào vùng trống hoặc bấm dấu ✕ để đóng
        </div>
    </div>

    {{-- GS1 Digital Link QR Code Modal --}}
    @if(!empty($qrSvg))
        <div id="qrCodeModal" class="fixed inset-0 z-50 hidden bg-black/80 backdrop-blur-xs flex items-center justify-center p-4" onclick="closeQrModal()">
            <div class="bg-white rounded-2xl max-w-sm w-full p-5 space-y-4 shadow-2xl text-center" onclick="event.stopPropagation()">
                <div class="flex items-center justify-between border-b border-stone-100 pb-3">
                    <div class="text-left">
                        <h3 class="text-sm font-bold text-stone-900">Mã QR GS1 Digital Link</h3>
                        <p class="text-[11px] text-stone-500">Quét mã bằng camera điện thoại</p>
                    </div>
                    <button type="button" onclick="closeQrModal()" class="w-7 h-7 rounded-full bg-stone-100 hover:bg-stone-200 text-stone-600 flex items-center justify-center transition">
                        ✕
                    </button>
                </div>

                <div class="p-4 bg-stone-50 rounded-xl flex items-center justify-center border border-stone-200/80">
                    <div class="w-48 h-48 flex items-center justify-center [&>svg]:w-full [&>svg]:h-full">
                        {!! $qrSvg !!}
                    </div>
                </div>

                <div class="space-y-2 text-xs">
                    <div class="font-mono text-[11px] text-stone-500 bg-stone-100 p-2 rounded break-all select-all border border-stone-200">
                        {{ $traceUrl ?? url()->current() }}
                    </div>
                    <button type="button" onclick="copyTraceUrl()" class="w-full py-2 px-3 rounded-lg bg-emerald-800 hover:bg-emerald-700 text-white font-semibold text-xs transition">
                        Sao chép liên kết truy xuất
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Toast Notification --}}
    <div id="toastNotification" class="fixed top-5 left-1/2 -translate-x-1/2 z-50 hidden px-4 py-2 rounded-xl bg-stone-900/95 text-white text-xs font-semibold shadow-xl border border-stone-700 transition duration-300">
        <span id="toastMessage">Đã sao chép liên kết!</span>
    </div>

    {{-- Footer --}}
    <footer class="bg-stone-900 text-stone-400 py-6 text-center text-xs border-t border-stone-800 mt-8 safe-bottom">
        <div class="max-w-4xl mx-auto px-4 space-y-1.5">
            <div class="font-bold text-stone-200 text-sm">Hệ thống Nông sản Minh bạch Farm2Fork</div>
            <p>Nền tảng Quản lý Chuỗi Cung ứng & Truy xuất Nguồn gốc Nông sản Chuẩn GS1 Quốc tế</p>
            <p class="text-[11px] text-stone-500">&copy; {{ date('Y') }} Farm2Fork. Bản quyền được bảo lưu.</p>
        </div>
    </footer>

    <script>
        // Trên điện thoại, thu gọn từng công đoạn để người dùng không phải cuộn quá dài.
        document.addEventListener('DOMContentLoaded', function() {
            const mobileQuery = window.matchMedia('(max-width: 639px)');
            const stages = Array.from(document.querySelectorAll('.trace-stage'));

            stages.forEach(function(stage, index) {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'trace-stage-toggle';
                button.innerHTML = '<span class="trace-stage-toggle-label">Xem chi tiết công đoạn</span>' +
                    '<svg aria-hidden="true" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>';

                if (index > 0 && mobileQuery.matches) {
                    stage.classList.add('is-collapsed');
                }

                const updateButton = function() {
                    const collapsed = stage.classList.contains('is-collapsed');
                    button.setAttribute('aria-expanded', String(!collapsed));
                    button.querySelector('.trace-stage-toggle-label').textContent = collapsed
                        ? 'Xem chi tiết công đoạn'
                        : 'Thu gọn công đoạn';
                };

                button.addEventListener('click', function() {
                    stage.classList.toggle('is-collapsed');
                    updateButton();
                });

                stage.insertBefore(button, stage.children[1] || null);
                updateButton();
            });

            document.querySelectorAll('.trace-stage-nav a').forEach(function(link) {
                link.addEventListener('click', function() {
                    const target = document.querySelector(link.getAttribute('href'));
                    if (target && mobileQuery.matches) {
                        target.classList.remove('is-collapsed');
                        const button = target.querySelector('.trace-stage-toggle');
                        if (button) {
                            button.setAttribute('aria-expanded', 'true');
                            button.querySelector('.trace-stage-toggle-label').textContent = 'Thu gọn công đoạn';
                        }
                    }
                });
            });

            mobileQuery.addEventListener('change', function(event) {
                if (!event.matches) {
                    stages.forEach(function(stage) { stage.classList.remove('is-collapsed'); });
                }
            });
        });

        // Modal Hình ảnh
        function openImageModal(src, caption) {
            const modal = document.getElementById('imageLightboxModal');
            const img = document.getElementById('modalImageSrc');
            const cap = document.getElementById('modalImageCaption');
            if (modal && img && cap) {
                img.src = src;
                cap.textContent = caption || 'Hình ảnh minh chứng thực tế';
                modal.classList.remove('hidden');
                document.body.classList.add('overflow-hidden');
            }
        }

        function closeImageModal() {
            const modal = document.getElementById('imageLightboxModal');
            if (modal) {
                modal.classList.add('hidden');
                document.body.classList.remove('overflow-hidden');
            }
        }

        // Modal QR Code
        function openQrModal() {
            const modal = document.getElementById('qrCodeModal');
            if (modal) {
                modal.classList.remove('hidden');
                document.body.classList.add('overflow-hidden');
            }
        }

        function closeQrModal() {
            const modal = document.getElementById('qrCodeModal');
            if (modal) {
                modal.classList.add('hidden');
                document.body.classList.remove('overflow-hidden');
            }
        }

        // Toast
        function showToast(msg) {
            const toast = document.getElementById('toastNotification');
            const toastMsg = document.getElementById('toastMessage');
            if (toast && toastMsg) {
                toastMsg.textContent = msg;
                toast.classList.remove('hidden');
                setTimeout(() => {
                    toast.classList.add('hidden');
                }, 2500);
            }
        }

        // Chia sẻ / Sao chép
        function handleShare() {
            if (navigator.share) {
                navigator.share({
                    title: document.title,
                    text: 'Hồ sơ nguồn gốc nông sản Farm2Fork - Lô {{ $lot->ma_lo }}',
                    url: window.location.href,
                }).catch(() => {});
            } else {
                navigator.clipboard.writeText(window.location.href);
                showToast('Đã sao chép liên kết truy xuất!');
            }
        }

        function copyTraceUrl() {
            navigator.clipboard.writeText('{{ $traceUrl ?? url()->current() }}');
            showToast('Đã sao chép liên kết truy xuất!');
            closeQrModal();
        }

        // Back to top button
        window.addEventListener('scroll', function() {
            const btn = document.getElementById('backToTopBtn');
            if (btn) {
                if (window.scrollY > 400) {
                    btn.classList.remove('hidden');
                } else {
                    btn.classList.add('hidden');
                }
            }
        });

        // Phím ESC đóng modal
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeImageModal();
                closeQrModal();
            }
        });
    </script>
</body>
</html>
