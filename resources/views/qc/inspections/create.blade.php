@extends('layouts.dashboard', ['title' => 'Lập phiếu kiểm định chất lượng', 'breadcrumb' => 'Farm2Fork · Kiểm định chất lượng (QC)'])

@section('content')
<div class="space-y-6 f2f-qc f2f-qc-form">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-stone-500">
                <a href="{{ route('qc.inspections.index') }}" class="hover:text-emerald-700 transition">Sổ kiểm định</a>
                <span>/</span>
                <span class="text-stone-800">Lập phiếu mới</span>
            </div>
            <h1 class="mt-1 text-2xl font-bold tracking-tight text-stone-900">
                Lập Phiếu & Nhập Kết quả Kiểm định Chất lượng
            </h1>
            <p class="mt-1 text-xs text-stone-600">
                Ghi nhận chỉ tiêu lý hóa, độ brix, cảm quan và an toàn thực phẩm cho lô nông sản
            </p>
        </div>

        <div class="flex items-center gap-2.5">
            <a href="{{ route('qc.pending.index') }}" class="f2f-btn-secondary text-xs">
                Danh sách chờ kiểm định
            </a>
            <a href="{{ route('qc.inspections.index') }}" class="f2f-btn-secondary text-xs">
                Xem lịch sử kiểm định
            </a>
        </div>
    </div>

    @if ($errors->any())
        <div class="f2f-alert f2f-alert-error">
            <div class="font-bold mb-1">Vui lòng kiểm tra lại các trường thông tin:</div>
            <ul class="list-disc list-inside space-y-0.5 text-xs">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('qc.inspections.store') }}" method="POST" id="inspectionForm" class="space-y-6">
        @csrf

        {{-- Card 1: General Lot & Inspection Info --}}
        <div class="f2f-card">
            <h2 class="f2f-card-title">1. Thông tin Lô Nông sản & Loại hình Kiểm định</h2>
            <p class="f2f-subtitle">Xác định đối tượng lô hàng và quy chuẩn kiểm định tương ứng.</p>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                <div class="md:col-span-2">
                    <label class="f2f-label" for="lo_nong_san_id">
                        Lô nông sản kiểm định <span class="text-rose-600">*</span>
                    </label>
                    <select class="f2f-input" name="lo_nong_san_id" id="lo_nong_san_id" required onchange="onLotChange(this)">
                        <option value="">-- Chọn lô nông sản cần kiểm định --</option>
                        @foreach($availableLots as $lot)
                            <option value="{{ $lot->lo_nong_san_id }}"
                                    data-type="{{ $lot->loai_lo }}"
                                    data-code="{{ $lot->ma_lo }}"
                                    data-product="{{ $lot->sanPham->ten_san_pham ?? '' }}"
                                    data-qty="{{ number_format($lot->so_luong, 2) }} {{ $lot->don_vi_tinh }}"
                                    data-has-prior="{{ $lot->phieuKiemDinh->isNotEmpty() ? '1' : '0' }}"
                                    @selected(old('lo_nong_san_id', $selectedLot?->lo_nong_san_id) == $lot->lo_nong_san_id)>
                                [{{ $lot->loai_lo === 'NGUYEN_LIEU' ? 'Nguyên liệu' : 'Thành phẩm' }}]
                                {{ $lot->ma_lo }} - {{ $lot->sanPham->ten_san_pham ?? 'Chưa xác định' }}
                                ({{ number_format($lot->so_luong, 2) }} {{ $lot->don_vi_tinh }})
                                - {{ \App\Models\LoNongSan::TRANG_THAI_LABELS[$lot->trang_thai] ?? $lot->trang_thai }}
                            </option>
                        @endforeach
                    </select>
                    <button type="button" id="openLotProfile" class="f2f-qc-lot-profile-link" aria-haspopup="dialog" aria-controls="lotProfileModal">
                        Xem thông tin lô
                        <span aria-hidden="true">→</span>
                    </button>
                </div>

                <div>
                    <label class="f2f-label" for="loai_kiem_dinh">
                        Loại hình kiểm định <span class="text-rose-600">*</span>
                    </label>
                    <select class="f2f-input" name="loai_kiem_dinh" id="loai_kiem_dinh" required onchange="onTypeChange(this.value)">
                        <option value="NHAP_KHO" @selected(old('loai_kiem_dinh', ($selectedLot?->loai_lo === 'THANH_PHAM' ? 'TRUOC_XUAT_XUONG' : 'NHAP_KHO')) === 'NHAP_KHO')>
                            Kiểm định nhập kho sơ chế (Nguyên liệu đầu vào)
                        </option>
                        <option value="TRUOC_XUAT_XUONG" @selected(old('loai_kiem_dinh', ($selectedLot?->loai_lo === 'THANH_PHAM' ? 'TRUOC_XUAT_XUONG' : 'NHAP_KHO')) === 'TRUOC_XUAT_XUONG')>
                            Kiểm định trước xuất xưởng (Lô thành phẩm)
                        </option>
                        <option value="KIEM_DINH_LAI" @selected(old('loai_kiem_dinh') === 'KIEM_DINH_LAI')>
                            Tái kiểm định chất lượng (Sau phân loại / khắc phục)
                        </option>
                    </select>
                </div>

                <div>
                    <label class="f2f-label" for="ngay_kiem_dinh">
                        Thời gian kiểm định thực tế <span class="text-rose-600">*</span>
                    </label>
                    <input class="f2f-input font-mono" type="datetime-local" name="ngay_kiem_dinh" id="ngay_kiem_dinh"
                           value="{{ old('ngay_kiem_dinh', now()->format('Y-m-d\TH:i')) }}" required>
                </div>

                <div>
                    <label class="f2f-label">Chuyên viên kiểm định (QC Tester)</label>
                    <input class="f2f-input bg-stone-100" type="text" value="{{ auth()->user()->ho_ten }} ({{ auth()->user()->so_dien_thoai }})" disabled>
                </div>

                <div>
                    <label class="f2f-label">Mã số phiếu kiểm định dự kiến</label>
                    <input class="f2f-input font-mono bg-stone-100 font-bold" type="text" value="{{ $suggestedCode }}" disabled>
                    <span class="text-[11px] text-stone-500 mt-0.5 block">Hệ thống sẽ cấp số thứ tự chuẩn xác khi lưu</span>
                </div>
            </div>
        </div>

        {{-- Card 2: Detailed Criteria Inspection Table --}}
        <div class="f2f-card">
            <div class="f2f-card-toolbar">
                <div>
                    <h2 class="f2f-card-title">2. Chi tiết Kết quả Kiểm nghiệm Từng Chỉ tiêu</h2>
                    <p class="f2f-subtitle">Đánh giá từng chỉ tiêu cảm quan, thông số lý hóa, dư lượng bảo vệ thực vật theo tiêu chuẩn.</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <button type="button" onclick="loadTemplate('inbound')" class="f2f-btn-secondary text-xs">
                        Nạp mẫu Nhập kho
                    </button>
                    <button type="button" onclick="loadTemplate('outbound')" class="f2f-btn-secondary text-xs">
                        Nạp mẫu Xuất xưởng
                    </button>
                    <button type="button" onclick="addCriteriaRow()" class="f2f-btn-primary text-xs">
                        Thêm chỉ tiêu mới
                    </button>
                </div>
            </div>

            <div class="f2f-table-container mt-4">
                <table class="f2f-table" id="criteriaTable">
                    <thead>
                        <tr>
                            <th style="width:25%">Tên chỉ tiêu kiểm nghiệm</th>
                            <th style="width:20%">Tiêu chuẩn tham chiếu</th>
                            <th style="width:20%">Giá trị đo thực tế</th>
                            <th style="width:10%">Đơn vị</th>
                            <th style="width:12%; text-align:center">Kết luận</th>
                            <th style="width:13%; text-align:right">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody id="criteriaTableBody">
                        {{-- Rows injected by JavaScript or server-rendered --}}
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Card 3: Overall Conclusion & Treatment Direction --}}
        <div class="f2f-card">
            <h2 class="f2f-card-title">3. Kết luận Tổng thể & Hướng Xử lý Tiếp theo</h2>
            <p class="f2f-subtitle">Xác định tính hợp lệ của toàn bộ lô hàng để phê duyệt đưa vào sơ chế hoặc xuất kho phân phối.</p>

            <div class="mt-4 space-y-4">
                <div>
                    <label class="f2f-label block mb-2">Kết luận kiểm định chất lượng: <span class="text-rose-600">*</span></label>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <label class="flex items-center gap-3 p-3.5 rounded-xl border border-stone-200 bg-stone-50 cursor-pointer hover:border-emerald-500 transition">
                            <input type="radio" name="ket_luan" value="DAT" class="text-emerald-700"
                                   @checked(old('ket_luan', 'DAT') === 'DAT') onchange="toggleFailFields(false)">
                            <div>
                                <span class="font-bold text-emerald-900 block text-sm">ĐẠT TIÊU CHUẨN XUẤT XƯỞNG / NHẬP KHO</span>
                                <span class="text-xs text-stone-500">Lô nông sản đáp ứng 100% tiêu chuẩn chất lượng và an toàn thực phẩm</span>
                            </div>
                        </label>

                        <label class="flex items-center gap-3 p-3.5 rounded-xl border border-stone-200 bg-stone-50 cursor-pointer hover:border-rose-500 transition">
                            <input type="radio" name="ket_luan" value="KHONG_DAT" class="text-rose-700"
                                   @checked(old('ket_luan') === 'KHONG_DAT') onchange="toggleFailFields(true)">
                            <div>
                                <span class="font-bold text-rose-900 block text-sm">KHÔNG ĐẠT TIÊU CHUẨN</span>
                                <span class="text-xs text-stone-500">Có chỉ tiêu không đạt, cần hướng xử lý phân loại, cách ly hoặc tiêu hủy</span>
                            </div>
                        </label>
                    </div>
                </div>

                {{-- Failure Treatment Fields (Shown when KHONG_DAT is selected) --}}
                <div id="failTreatmentSection" class="p-4 rounded-xl border border-rose-200 bg-rose-50/50 space-y-3 {{ old('ket_luan') === 'KHONG_DAT' ? '' : 'hidden' }}">
                    <div class="text-xs font-bold uppercase tracking-wider text-rose-800">
                        Phương án xử lý đối với lô hàng không đạt:
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="f2f-label" for="huong_xu_ly">
                                Hướng xử lý tiếp theo <span class="text-rose-600">*</span>
                            </label>
                            <select class="f2f-input" name="huong_xu_ly" id="huong_xu_ly">
                                <option value="">-- Chọn phương án xử lý --</option>
                                <option value="LOAI_BO_VA_KIEM_LAI" @selected(old('huong_xu_ly') === 'LOAI_BO_VA_KIEM_LAI')>
                                    Phân loại lại, loại bỏ quả hư hỏng & Tái kiểm định
                                </option>
                                <option value="TRA_NHA_CUNG_CAP" @selected(old('huong_xu_ly') === 'TRA_NHA_CUNG_CAP')>
                                    Trả lại nhà cung cấp / Hợp tác xã vùng trồng
                                </option>
                                <option value="THU_HOI_LO" @selected(old('huong_xu_ly') === 'THU_HOI_LO')>
                                    Thu hồi toàn bộ lô & Tiêu hủy an toàn
                                </option>
                                <option value="HA_CAP_CHAT_LUONG" @selected(old('huong_xu_ly') === 'HA_CAP_CHAT_LUONG')>
                                    Hạ cấp chất lượng thương mại / Chuyển chế biến phụ
                                </option>
                                <option value="XU_LY_BO_SUNG" @selected(old('huong_xu_ly') === 'XU_LY_BO_SUNG')>
                                    Lưu kho lạnh xử lý bổ sung
                                </option>
                            </select>
                        </div>

                        <div>
                            <label class="f2f-label" for="ghi_chu_loi">Mô tả nguyên nhân lỗi / Không đạt</label>
                            <input class="f2f-input" type="text" name="ghi_chu_loi" id="ghi_chu_loi"
                                   value="{{ old('ghi_chu_loi') }}" placeholder="Vd: Tỷ lệ quả dập vượt ngưỡng 7%, phát hiện nấm mốc...">
                        </div>
                    </div>
                </div>

                <div>
                    <label class="f2f-label" for="ghi_chu">Ghi chú kiểm định chung</label>
                    <textarea class="f2f-input" name="ghi_chu" id="ghi_chu" rows="2"
                              placeholder="Nhập ghi chú cảm quan, điều kiện phòng lab hoặc nhận xét của kiểm nghiệm viên...">{{ old('ghi_chu') }}</textarea>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 mt-6 pt-4 border-t border-stone-200">
                <a href="{{ route('qc.pending.index') }}" class="f2f-btn-secondary">
                    Hủy bỏ
                </a>
                <button type="submit" class="f2f-btn-primary">
                    Lưu phiếu kiểm định & Cập nhật trạng thái lô
                </button>
            </div>
        </div>
    </form>

    <div id="lotProfileModal" class="f2f-qc-lot-modal" hidden role="dialog" aria-modal="true" aria-labelledby="lotProfileTitle">
        <div class="f2f-qc-lot-dialog" role="document">
            <div class="f2f-qc-lot-modal-head">
                <div>
                    <span>Đối chiếu trước khi kiểm định</span>
                    <h2 id="lotProfileTitle">Thông tin lô nông sản</h2>
                </div>
                <button type="button" data-close-lot-profile aria-label="Đóng thông tin lô">×</button>
            </div>

            <div class="f2f-qc-lot-summary">
                <div><small>Mã lô</small><strong data-lot-field="code">—</strong></div>
                <span data-lot-field="status">—</span>
            </div>

            <dl class="f2f-qc-lot-details">
                <div><dt>Sản phẩm</dt><dd data-lot-field="product">—</dd></div>
                <div><dt>Loại lô</dt><dd data-lot-field="type">—</dd></div>
                <div><dt>Khối lượng</dt><dd data-lot-field="quantity">—</dd></div>
                <div><dt>Đơn vị tạo lô</dt><dd data-lot-field="origin">—</dd></div>
                <div><dt>Mùa vụ</dt><dd data-lot-field="season">—</dd></div>
                <div><dt>Vùng trồng</dt><dd data-lot-field="zone">—</dd></div>
                <div><dt>Ngày thu hoạch</dt><dd data-lot-field="harvest_date">—</dd></div>
                <div><dt>Ngày sản xuất</dt><dd data-lot-field="production_date">—</dd></div>
                <div><dt>Kho hiện tại</dt><dd data-lot-field="warehouse">—</dd></div>
                <div><dt>Mã sản phẩm</dt><dd data-lot-field="product_code">—</dd></div>
                <div><dt>GTIN</dt><dd data-lot-field="gtin">—</dd></div>
                <div id="lotInputLotsRow"><dt>Lô nguyên liệu đầu vào</dt><dd data-lot-field="input_lots">—</dd></div>
            </dl>

            <div class="f2f-qc-lot-modal-actions">
                <a href="#" id="lotTraceLink" target="_blank" rel="noopener">Mở hồ sơ truy xuất</a>
                <button type="button" class="f2f-btn-primary" data-close-lot-profile>Đã đối chiếu</button>
            </div>
        </div>
    </div>
</div>

<script>
    const defaultInbound = @json($defaultCriteriaInbound);
    const defaultOutbound = @json($defaultCriteriaOutbound);
    const lotProfiles = @json($lotProfiles);
    let rowCount = 0;

    function renderCriteriaRows(items) {
        const tbody = document.getElementById('criteriaTableBody');
        tbody.innerHTML = '';
        rowCount = 0;
        items.forEach(item => {
            addCriteriaRow(item);
        });
    }

    function addCriteriaRow(item = null) {
        const tbody = document.getElementById('criteriaTableBody');
        const idx = rowCount++;

        const tr = document.createElement('tr');
        tr.id = `criteria-row-${idx}`;
        tr.className = 'hover:bg-stone-50/70 transition';

        const nameVal = item ? (item.ten_tieu_chi || '') : '';
        const stdVal = item ? (item.gia_tri_tham_chieu || '') : '';
        const actualVal = item ? (item.gia_tri_thuc_te || '') : '';
        const unitVal = item ? (item.don_vi_do || '') : '';
        const resVal = item ? (item.ket_qua || 'DAT') : 'DAT';
        const noteVal = item ? (item.ghi_chu || '') : '';

        tr.innerHTML = `
            <td>
                <input class="f2f-input text-xs" type="text" name="criteria[${idx}][ten_tieu_chi]" value="${escapeHtml(nameVal)}" required placeholder="Tên tiêu chí kiểm tra">
            </td>
            <td>
                <input class="f2f-input text-xs font-mono" type="text" name="criteria[${idx}][gia_tri_tham_chieu]" value="${escapeHtml(stdVal)}" placeholder="Vd: 0-4, >= 7, Âm tính">
            </td>
            <td>
                <input class="f2f-input text-xs font-mono font-semibold text-stone-800" type="text" name="criteria[${idx}][gia_tri_thuc_te]" value="${escapeHtml(actualVal)}" placeholder="Giá trị đo được">
            </td>
            <td>
                <input class="f2f-input text-xs font-mono" type="text" name="criteria[${idx}][don_vi_do]" value="${escapeHtml(unitVal)}" placeholder="°C, %...">
            </td>
            <td style="text-align:center">
                <select class="f2f-input text-xs font-bold" name="criteria[${idx}][ket_qua]" onchange="checkAutoOverallConclusion()">
                    <option value="DAT" ${resVal === 'DAT' ? 'selected' : ''}>ĐẠT</option>
                    <option value="KHONG_DAT" ${resVal === 'KHONG_DAT' ? 'selected' : ''}>KHÔNG ĐẠT</option>
                </select>
            </td>
            <td style="text-align:right">
                <button type="button" onclick="removeCriteriaRow(${idx})" class="text-rose-700 hover:text-rose-900 text-xs font-semibold px-2 py-1 rounded hover:bg-rose-50 transition">
                    Xóa
                </button>
            </td>
        `;

        tbody.appendChild(tr);
    }

    function removeCriteriaRow(idx) {
        const row = document.getElementById(`criteria-row-${idx}`);
        if (row) {
            row.remove();
        }
        checkAutoOverallConclusion();
    }

    function loadTemplate(type) {
        if (type === 'outbound') {
            renderCriteriaRows(defaultOutbound);
        } else {
            renderCriteriaRows(defaultInbound);
        }
    }

    function onTypeChange(type) {
        if (type === 'TRUOC_XUAT_XUONG') {
            loadTemplate('outbound');
        } else {
            loadTemplate('inbound');
        }
    }

    function onLotChange(select) {
        const option = select.options[select.selectedIndex];
        if (!option) return;
        updateLotProfileTrigger();
        const lotType = option.getAttribute('data-type');
        const hasPriorInspection = option.getAttribute('data-has-prior') === '1';
        const typeSelect = document.getElementById('loai_kiem_dinh');
        if (hasPriorInspection) {
            typeSelect.value = 'KIEM_DINH_LAI';
            loadTemplate(lotType === 'THANH_PHAM' ? 'outbound' : 'inbound');
        } else if (lotType === 'THANH_PHAM') {
            typeSelect.value = 'TRUOC_XUAT_XUONG';
            loadTemplate('outbound');
        } else {
            typeSelect.value = 'NHAP_KHO';
            loadTemplate('inbound');
        }
    }

    function currentLotProfile() {
        const lotId = document.getElementById('lo_nong_san_id').value;
        return lotId ? lotProfiles[lotId] : null;
    }

    function updateLotProfileTrigger() {
        const trigger = document.getElementById('openLotProfile');
        const hasLot = Boolean(currentLotProfile());
        trigger.disabled = !hasLot;
        trigger.setAttribute('aria-disabled', hasLot ? 'false' : 'true');
    }

    function openLotProfile() {
        const profile = currentLotProfile();
        if (!profile) return;

        document.querySelectorAll('[data-lot-field]').forEach((node) => {
            const key = node.dataset.lotField;
            const value = key === 'input_lots'
                ? (profile.input_lots?.length ? profile.input_lots.join(', ') : 'Không áp dụng')
                : (profile[key] || '—');
            node.textContent = value;
        });

        document.getElementById('lotInputLotsRow').hidden = profile.type !== 'Lô thành phẩm';
        document.getElementById('lotTraceLink').href = profile.trace_url;

        const modal = document.getElementById('lotProfileModal');
        modal.hidden = false;
        document.body.style.overflow = 'hidden';
        modal.querySelector('[data-close-lot-profile]').focus();
    }

    function closeLotProfile() {
        const modal = document.getElementById('lotProfileModal');
        if (modal.hidden) return;
        modal.hidden = true;
        document.body.style.overflow = '';
        document.getElementById('openLotProfile').focus();
    }

    function toggleFailFields(isFailed) {
        const sec = document.getElementById('failTreatmentSection');
        const select = document.getElementById('huong_xu_ly');
        if (isFailed) {
            sec.classList.remove('hidden');
            select.setAttribute('required', 'required');
        } else {
            sec.classList.add('hidden');
            select.removeAttribute('required');
        }
    }

    function checkAutoOverallConclusion() {
        const selects = document.querySelectorAll('#criteriaTableBody select');
        let hasFail = false;
        selects.forEach(s => {
            if (s.value === 'KHONG_DAT') hasFail = true;
        });

        const datRadio = document.querySelector('input[name="ket_luan"][value="DAT"]');
        const failRadio = document.querySelector('input[name="ket_luan"][value="KHONG_DAT"]');

        if (hasFail) {
            failRadio.checked = true;
            toggleFailFields(true);
        } else {
            datRadio.checked = true;
            toggleFailFields(false);
        }
    }

    function escapeHtml(text) {
        return text.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    // Initialize with selected lot or default template
    document.addEventListener('DOMContentLoaded', function () {
        const lotSelect = document.getElementById('lo_nong_san_id');
        if (lotSelect.value) {
            onLotChange(lotSelect);
        } else {
            const currentType = document.getElementById('loai_kiem_dinh').value;
            onTypeChange(currentType);
        }
        updateLotProfileTrigger();

        document.getElementById('openLotProfile').addEventListener('click', openLotProfile);
        document.querySelectorAll('[data-close-lot-profile]').forEach((button) => {
            button.addEventListener('click', closeLotProfile);
        });
        document.getElementById('lotProfileModal').addEventListener('click', function (event) {
            if (event.target === this) closeLotProfile();
        });
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') closeLotProfile();
        });
    });
</script>
@endsection

