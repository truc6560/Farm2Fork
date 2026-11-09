@extends('layouts.dashboard', ['title' => 'Khai báo hồ sơ vùng trồng mới', 'breadcrumb' => 'Farm2Fork · Vùng trồng · Khai báo'])

@section('content')
<div class="f2f-card f2f-form-card">
    <div class="f2f-card-toolbar">
        <div>
            <h2 class="f2f-card-title">Khai báo hồ sơ vùng trồng</h2>
            <p class="f2f-subtitle">Lưu trữ và quản lý thông tin vùng canh tác sau khi đã được cơ quan có thẩm quyền cấp mã số vùng trồng (MSVT) hoặc có tài liệu chứng nhận minh chứng.</p>
        </div>
        <div class="f2f-toolbar-actions">
            <a href="{{ route('producer.planting-zones.index') }}" class="f2f-btn-ghost">Danh sách vùng trồng</a>
        </div>
    </div>

    @if ($errors->any())
        <div class="p-3 mb-4 rounded-lg bg-rose-50 border border-rose-200 text-rose-700 text-xs">
            <strong class="font-bold block mb-1">Vui lòng kiểm tra lại các thông tin sau:</strong>
            <ul class="list-disc list-inside space-y-0.5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('producer.planting-zones.store') }}" enctype="multipart/form-data" class="f2f-form-body">
        @csrf

        @if(!empty($allOrgs) && $allOrgs->isNotEmpty())
            <div class="f2f-form-section">
                <h3 class="f2f-section-title">Tổ chức sở hữu (Dành cho Quản trị viên)</h3>
                <div class="f2f-field">
                    <label class="f2f-label" for="to_chuc_id">Chọn tổ chức sản xuất <span class="text-rose-500">*</span></label>
                    <select class="f2f-input" id="to_chuc_id" name="to_chuc_id" required>
                        @foreach($allOrgs as $org)
                            <option value="{{ $org->to_chuc_id }}" @selected(old('to_chuc_id', $defaultOrgId) == $org->to_chuc_id)>{{ $org->ten_to_chuc }} ({{ $org->ma_to_chuc }})</option>
                        @endforeach
                    </select>
                </div>
            </div>
        @endif

        {{-- 1. Định danh Mã số vùng trồng (MSVT) & Tệp chứng nhận minh chứng --}}
        <div class="f2f-form-section">
            <h3 class="f2f-section-title">1. Thông tin mã số vùng trồng (MSVT) & Minh chứng</h3>
            <p class="f2f-subtitle">Yêu cầu có ít nhất Mã số vùng trồng hoặc Tệp tài liệu chứng nhận được cấp để xác lập hồ sơ chính thức.</p>

            <div class="f2f-form-grid">
                <div class="f2f-field">
                    <label class="f2f-label" for="ma_so_vung_trong">
                        Mã số vùng trồng (MSVT) được cấp
                    </label>
                    <input class="f2f-input font-mono font-bold text-emerald-900 tracking-wide uppercase" 
                           id="ma_so_vung_trong" 
                           name="ma_so_vung_trong" 
                           value="{{ old('ma_so_vung_trong') }}" >
                </div>

                <div class="f2f-field">
                    <label class="f2f-label" for="tep_chung_nhan">
                        Tải lên tệp chứng nhận / Tài liệu minh chứng MSVT
                    </label>
                    <input class="f2f-input" 
                           type="file" 
                           id="tep_chung_nhan" 
                           name="tep_chung_nhan" 
                           accept=".pdf,image/png,image/jpeg,image/jpg">
                    <span class="text-[11px] text-stone-500 block mt-1">
                        Định dạng: PDF hoặc Ảnh chụp (JPG, PNG), dung lượng tối đa 5MB.
                    </span>
                </div>
            </div>
        </div>

        {{-- 2. Thông tin vùng trồng --}}
        <div class="f2f-form-section">
            <h3 class="f2f-section-title">2. Thông tin vùng canh tác</h3>
            <p class="f2f-subtitle">Khai báo quy mô, cây trồng và địa chỉ thực địa phục vụ quản lý mùa vụ và truy xuất nguồn gốc.</p>

            <div class="f2f-form-grid">
                <div class="f2f-field">
                    <label class="f2f-label" for="cay_trong_chinh">
                        Cây trồng chủ lực <span class="text-rose-500">*</span>
                    </label>
                    <input class="f2f-input" 
                           id="cay_trong_chinh" 
                           name="cay_trong_chinh" 
                           value="{{ old('cay_trong_chinh') }}" 
                           placeholder="Ví dụ: Dâu tây, Xà lách Romaine, Cà chua bi, Sầu riêng..." 
                           required>
                    <span class="text-[11px] text-stone-500 block mt-1">Loại nông sản / cây trồng chính được canh tác tại vùng này.</span>
                </div>

                <div class="f2f-field">
                    <label class="f2f-label" for="dien_tich_ha">
                        Diện tích canh tác (ha) <span class="text-rose-500">*</span>
                    </label>
                    <input class="f2f-input" 
                           type="number" 
                           step="0.001" 
                           min="0.001" 
                           id="dien_tich_ha" 
                           name="dien_tich_ha" 
                           value="{{ old('dien_tich_ha') }}" 
                           placeholder="Ví dụ: 1.250" 
                           required>
                    <span class="text-[11px] text-stone-500 block mt-1">Diện tích đất canh tác thực tế (phải lớn hơn 0 ha).</span>
                </div>

                <div class="f2f-field f2f-col-span-2">
                    <label class="f2f-label" for="dia_chi">
                        Địa chỉ chi tiết vùng trồng <span class="text-rose-500">*</span>
                    </label>
                    <input class="f2f-input" 
                           id="dia_chi" 
                           name="dia_chi" 
                           value="{{ old('dia_chi') }}" 
                           placeholder="Ví dụ: Thôn Thái Phiên, Phường 12, TP. Đà Lạt, Tỉnh Lâm Đồng" 
                           required>
                    <span class="text-[11px] text-stone-500 block mt-1">Địa chỉ thực địa khu vực sản xuất để khách hàng tra cứu nguồn gốc.</span>
                </div>

                <div class="f2f-field">
                    <label class="f2f-label" for="trang_thai">
                        Trạng thái hoạt động <span class="text-rose-500">*</span>
                    </label>
                    <select class="f2f-input" id="trang_thai" name="trang_thai" required>
                        <option value="DANG_SU_DUNG" @selected(old('trang_thai', 'DANG_SU_DUNG') === 'DANG_SU_DUNG')>Đang canh tác / sẵn sàng sử dụng</option>
                        <option value="TAM_NGUNG" @selected(old('trang_thai') === 'TAM_NGUNG')>Tạm ngưng hoạt động</option>
                    </select>
                </div>
            </div>
        </div>

        {{-- 3. Tiêu chuẩn chất lượng canh tác (Tùy chọn) --}}
        <div class="f2f-form-section">
            <h3 class="f2f-section-title">3. Tiêu chuẩn chất lượng canh tác (Tùy chọn)</h3>
            <p class="f2f-subtitle">Khai báo thêm chứng chỉ an toàn thực phẩm nếu vùng trồng đã được đánh giá chứng nhận.</p>

            <div class="f2f-form-grid">
                <div class="f2f-field">
                    <label class="f2f-label" for="loai_chung_nhan">Tiêu chuẩn chứng nhận</label>
                    <select class="f2f-input" id="loai_chung_nhan" name="loai_chung_nhan">
                        <option value="">-- Chưa có chứng nhận tiêu chuẩn --</option>
                        <option value="VIETGAP" @selected(old('loai_chung_nhan') === 'VIETGAP')>VietGAP Trồng trọt</option>
                        <option value="GLOBALGAP" @selected(old('loai_chung_nhan') === 'GLOBALGAP')>GlobalGAP</option>
                        <option value="ORGANIC" @selected(old('loai_chung_nhan') === 'ORGANIC')>Nông nghiệp Hữu cơ (Organic)</option>
                        <option value="HACCP" @selected(old('loai_chung_nhan') === 'HACCP')>HACCP</option>
                        <option value="KHAC" @selected(old('loai_chung_nhan') === 'KHAC')>Tiêu chuẩn an toàn khác</option>
                    </select>
                </div>

                <div class="f2f-field">
                    <label class="f2f-label" for="so_chung_nhan">Số giấy chứng nhận</label>
                    <input class="f2f-input" id="so_chung_nhan" name="so_chung_nhan" value="{{ old('so_chung_nhan') }}" placeholder="Ví dụ: VG-DEMO-LD-0001">
                </div>

                <div class="f2f-field f2f-col-span-2">
                    <label class="f2f-label" for="don_vi_cap_chung_nhan">Tổ chức đánh giá & cấp chứng nhận</label>
                    <input class="f2f-input" id="don_vi_cap_chung_nhan" name="don_vi_cap_chung_nhan" value="{{ old('don_vi_cap_chung_nhan') }}" placeholder="Ví dụ: Trung tâm Chứng nhận Phù hợp Quacert, Tổ chức chứng nhận VietGAP...">
                </div>

                <div class="f2f-field">
                    <label class="f2f-label" for="ngay_cap_chung_nhan">Ngày cấp chứng nhận</label>
                    <input class="f2f-input" type="date" id="ngay_cap_chung_nhan" name="ngay_cap_chung_nhan" value="{{ old('ngay_cap_chung_nhan') }}">
                </div>

                <div class="f2f-field">
                    <label class="f2f-label" for="ngay_het_han_chung_nhan">Ngày hết hạn chứng nhận</label>
                    <input class="f2f-input" type="date" id="ngay_het_han_chung_nhan" name="ngay_het_han_chung_nhan" value="{{ old('ngay_het_han_chung_nhan') }}">
                </div>
            </div>
        </div>

        <div class="f2f-form-actions">
            <a href="{{ route('producer.planting-zones.index') }}" class="f2f-btn-ghost">Hủy bỏ</a>
            <button type="submit" class="f2f-btn-primary">Lưu hồ sơ vùng trồng</button>
        </div>
    </form>
</div>
@endsection
