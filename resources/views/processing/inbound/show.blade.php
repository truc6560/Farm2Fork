@extends('layouts.dashboard', ['title' => 'Lô nguyên liệu ' . $lot->ma_lo, 'breadcrumb' => 'Farm2Fork · Nhân viên sơ chế · Chi tiết nguyên liệu'])

@section('content')
<div class="f2f-detail-layout">
    <div class="f2f-detail-main">
        <div class="f2f-card">
            <div class="f2f-card-toolbar">
                <div>
                    <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
                        <h2 class="f2f-card-title" style="margin:0">Lô nguyên liệu: <code>{{ $lot->ma_lo }}</code></h2>
                        <span class="f2f-badge {{ $lot->getTrangThaiBadgeClass() }}">
                            {{ \App\Models\LoNongSan::TRANG_THAI_LABELS[$lot->trang_thai] ?? $lot->trang_thai }}
                        </span>
                        <span class="f2f-tag">Nguyên liệu đầu vào</span>
                    </div>
                    <p class="f2f-subtitle" style="margin-top:4px">
                        Nông sản: <strong>{{ $lot->sanPham->ten_san_pham ?? '—' }}</strong> · Khối lượng hiện có: 
                        <strong class="text-emerald-700">{{ number_format($lot->so_luong, 2) }} {{ $lot->don_vi_tinh }}</strong>
                    </p>
                </div>
                <div class="f2f-toolbar-actions">
                    @if($lot->trang_thai === 'CHO_SO_CHE' && (float)$lot->so_luong > 0)
                        <a href="{{ route('processing.batches.create', ['nguyen_lieu_id' => $lot->lo_nong_san_id]) }}" class="f2f-btn-primary">
                            + Sơ chế & Tạo lô thành phẩm
                        </a>
                    @endif
                    <a href="{{ route('processing.inbound.index') }}" class="f2f-btn-ghost">
                        Danh sách nguyên liệu
                    </a>
                </div>
            </div>

            <div class="f2f-info-grid">
                <div class="f2f-info-item">
                    <span class="f2f-info-label">Loại nông sản</span>
                    <strong class="f2f-info-val">{{ $lot->sanPham->ten_san_pham ?? '—' }} ({{ $lot->sanPham->ma_san_pham ?? '' }})</strong>
                </div>
                <div class="f2f-info-item">
                    <span class="f2f-info-label">Khối lượng hiện còn</span>
                    <strong class="f2f-info-val text-emerald-800">{{ number_format($lot->so_luong, 2) }} {{ $lot->don_vi_tinh }}</strong>
                </div>
                <div class="f2f-info-item">
                    <span class="f2f-info-label">Ngày thu hoạch</span>
                    <strong class="f2f-info-val">{{ $lot->ngay_thu_hoach ? $lot->ngay_thu_hoach->format('d/m/Y') : '—' }}</strong>
                </div>
                <div class="f2f-info-item">
                    <span class="f2f-info-label">Hạn sử dụng khuyến nghị</span>
                    <strong class="f2f-info-val text-amber-600">{{ $lot->han_su_dung ? $lot->han_su_dung->format('d/m/Y') : '—' }}</strong>
                </div>
                <div class="f2f-info-item">
                    <span class="f2f-info-label">Vị trí kho bảo quản</span>
                    <strong class="f2f-info-val">{{ $lot->khoLanh->ten_kho ?? 'Chưa đưa vào kho' }}</strong>
                </div>
                <div class="f2f-info-item">
                    <span class="f2f-info-label">Mã NCC nội bộ</span>
                    <strong class="f2f-info-val">{{ $lot->ma_lo_nha_cung_cap ?? 'Không áp dụng' }}</strong>
                </div>
            </div>
        </div>

        <!-- Inbound Reception Form (if not yet received) -->
        @if($lot->trang_thai === 'MOI_TAO')
            <div class="f2f-card" style="margin-top:16px;border:2px solid #3b82f6;background:#f8faff">
                <div class="f2f-card-toolbar">
                    <div>
                        <h3 class="f2f-card-title">Xác nhận tiếp nhận lô nguyên liệu vào xưởng</h3>
                        <p class="f2f-subtitle">Cân kiểm tra khối lượng thực tế và chỉ định vị trí kho lạnh trước khi đưa vào sơ chế.</p>
                    </div>
                </div>

                <form method="POST" action="{{ route('processing.inbound.receive', $lot->lo_nong_san_id) }}" class="f2f-form-body">
                    @csrf
                    <div class="f2f-form-grid">
                        <div class="f2f-field f2f-col-span-2">
                            <label class="f2f-label" for="so_luong_thuc_nhan">Khối lượng thực nhận ({{ $lot->don_vi_tinh }}) <span class="text-rose-500">*</span></label>
                            <input class="f2f-input" type="number" step="0.01" min="0.01" id="so_luong_thuc_nhan" name="so_luong_thuc_nhan" value="{{ old('so_luong_thuc_nhan', $lot->so_luong) }}" required>
                            <span class="f2f-field-hint" style="margin-top:6px;display:block;color:#64748b;font-size:13px;">Tiếp nhận xong chuyển chờ kiểm định. Nhập kho được ghi nhận riêng tại màn hình kho.</span>
                        </div>
                        <div class="f2f-field f2f-col-span-2">
                            <label class="f2f-label" for="ghi_chu">Ghi chú tình trạng nguyên liệu khi tiếp nhận</label>
                            <input class="f2f-input" id="ghi_chu" name="ghi_chu" value="{{ old('ghi_chu') }}" placeholder="Ví dụ: Đạt độ tươi tiêu chuẩn, quả đồng đều, bao bì nguyên vẹn...">
                        </div>
                    </div>
                    <div style="margin-top:8px">
                        <button class="f2f-btn-primary" type="submit">Xác nhận tiếp nhận vào xưởng</button>
                    </div>
                </form>
            </div>
        @endif

        <!-- Downstream finished products from this raw lot -->
        <div class="f2f-card" style="margin-top:16px">
            <div class="f2f-card-toolbar">
                <div>
                    <h3 class="f2f-card-title">Các lô thành phẩm đã sơ chế từ nguyên liệu này ({{ $lot->thanhPhamDauRa->count() }})</h3>
                    <p class="f2f-subtitle">Truy xuất xuôi: Quá trình phân bổ lô nguyên liệu thô vào các lô thành phẩm sau sơ chế.</p>
                </div>
                @if($lot->trang_thai === 'CHO_SO_CHE' && (float)$lot->so_luong > 0)
                    <div class="f2f-toolbar-actions">
                        <a href="{{ route('processing.batches.create', ['nguyen_lieu_id' => $lot->lo_nong_san_id]) }}" class="f2f-btn-primary f2f-btn-sm">
                            + Sơ chế thêm lô mới
                        </a>
                    </div>
                @endif
            </div>

            <div class="f2f-table-wrap">
                <table class="f2f-table">
                    <thead>
                        <tr>
                            <th>Mã lô thành phẩm</th>
                            <th>Khối lượng NL sử dụng</th>
                            <th>Sản lượng thành phẩm</th>
                            <th>Ngày sơ chế</th>
                            <th>Hạn sử dụng</th>
                            <th>Trạng thái</th>
                            <th style="text-align:right">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($lot->thanhPhamDauRa as $fp)
                        <tr>
                            <td>
                                <a href="{{ route('processing.batches.show', $fp->lo_nong_san_id) }}" class="f2f-table-link">
                                    <code>{{ $fp->ma_lo }}</code>
                                </a>
                            </td>
                            <td>
                                <strong>{{ number_format($fp->pivot->khoi_luong_su_dung, 2) }} {{ $fp->pivot->don_vi_tinh }}</strong>
                            </td>
                            <td>
                                <strong class="text-emerald-700">{{ number_format($fp->so_luong, 2) }} {{ $fp->don_vi_tinh }}</strong>
                            </td>
                            <td>{{ $fp->ngay_san_xuat ? $fp->ngay_san_xuat->format('d/m/Y') : '—' }}</td>
                            <td>{{ $fp->han_su_dung ? $fp->han_su_dung->format('d/m/Y') : '—' }}</td>
                            <td>
                                <span class="f2f-badge {{ $fp->getTrangThaiBadgeClass() }}">
                                    {{ \App\Models\LoNongSan::TRANG_THAI_LABELS[$fp->trang_thai] ?? $fp->trang_thai }}
                                </span>
                            </td>
                            <td style="text-align:right">
                                <a href="{{ route('processing.batches.show', $fp->lo_nong_san_id) }}" class="f2f-btn-ghost f2f-btn-sm">
                                    Chi tiết
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="f2f-empty-cell">
                                <div class="f2f-empty-state">
                                    <p>Chưa có lô thành phẩm nào được sơ chế từ lô nguyên liệu này.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Sidebar Origin Details -->
    <div class="f2f-detail-side">
        <div class="f2f-card">
            <h3 class="f2f-card-title" style="margin-top:0">Nguồn gốc đồng ruộng</h3>
            @if($lot->muaVu)
                <div class="f2f-cert-box">
                    <div class="f2f-cert-title">{{ $lot->muaVu->vungTrong->ten_vung_trong ?? 'Vùng trồng' }}</div>
                    <div class="f2f-cert-row">
                        <span>Mùa vụ:</span>
                        <strong>{{ $lot->muaVu->ten_mua_vu }}</strong>
                    </div>
                    <div class="f2f-cert-row">
                        <span>Giống:</span>
                        <span>{{ $lot->muaVu->giong_cay_trong ?? 'Giống phổ thông' }}</span>
                    </div>
                    <div class="f2f-cert-row">
                        <span>MSVT:</span>
                        <strong>{{ $lot->muaVu->vungTrong->ma_so_vung_trong ?? 'Chưa cấp' }}</strong>
                    </div>
                    <div class="f2f-cert-row">
                        <span>Tiêu chuẩn:</span>
                        <strong class="text-emerald-700">{{ $lot->muaVu->vungTrong->loai_chung_nhan ?? 'Chưa chứng nhận' }}</strong>
                    </div>
                    <div class="f2f-cert-row">
                        <span>Đơn vị sở hữu:</span>
                        <span>{{ $lot->muaVu->vungTrong->toChuc->ten_to_chuc ?? '—' }}</span>
                    </div>
                    <div class="f2f-cert-row">
                        <span>Địa chỉ:</span>
                        <span>{{ $lot->muaVu->vungTrong->dia_chi ?? '—' }}</span>
                    </div>
                </div>
            @elseif($lot->toChucNguon)
                <div class="f2f-cert-box">
                    <div class="f2f-cert-title">{{ $lot->toChucNguon->ten_to_chuc }}</div>
                    <div class="f2f-cert-row">
                        <span>Mã nhà cung cấp:</span>
                        <strong>{{ $lot->toChucNguon->ma_to_chuc }}</strong>
                    </div>
                    <div class="f2f-cert-row">
                        <span>Địa chỉ:</span>
                        <span>{{ $lot->toChucNguon->dia_chi }}</span>
                    </div>
                    <div class="f2f-cert-row">
                        <span>Liên hệ:</span>
                        <span>{{ $lot->toChucNguon->so_dien_thoai ?? $lot->toChucNguon->email }}</span>
                    </div>
                </div>
            @else
                <div class="f2f-empty-state" style="padding:16px 8px">
                    <p>Không có thông tin tổ chức nguồn.</p>
                </div>
            @endif
        </div>

        <div class="f2f-card" style="margin-top:16px">
            <h3 class="f2f-card-title" style="margin-top:0">Định danh số GS1 Digital Link</h3>
            <p class="f2f-subtitle" style="font-size:13px">Truy xuất nguồn gốc nguyên liệu chuẩn quốc tế GS1.</p>
            <div class="f2f-digital-link-box">
                <input class="f2f-input" value="{{ $lot->digital_link_url }}" readonly>
            </div>
        </div>
    </div>
</div>
@endsection

