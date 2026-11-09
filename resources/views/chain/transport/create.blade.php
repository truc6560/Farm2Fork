@extends('layouts.dashboard', ['title' => 'Lập chuyến vận chuyển', 'breadcrumb' => 'Farm2Fork · Quản lý chuỗi · Vận chuyển'])

@section('content')
<div class="max-w-5xl mx-auto space-y-5">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wider text-stone-500">Điều phối vận tải</p>
            <h1 class="text-2xl font-bold tracking-tight text-stone-900">Lập chuyến & phân công tài xế</h1>
            <p class="mt-1 text-sm text-stone-600">Tạo kế hoạch chuyến lạnh. Nhân viên kho sẽ gắn kiện SSCC và phiếu giao trước khi tài xế khởi hành.</p>
        </div>
        <a href="{{ route('chain.transport.index') }}" class="f2f-btn-secondary">Quay lại danh sách</a>
    </div>

    @if($errors->any())
        <div class="f2f-alert f2f-alert-error">
            <ul class="list-disc list-inside text-sm">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    @if($drivers->isEmpty() || $vehicles->isEmpty())
        <div class="f2f-alert f2f-alert-warning">
            @if($drivers->isEmpty())<p>Không có tài xế đang hoạt động và chưa vướng chuyến khác.</p>@endif
            @if($vehicles->isEmpty())<p>Không có xe lạnh sẵn sàng kèm thiết bị IoT hoạt động. Hãy kiểm tra danh mục phương tiện và cảm biến.</p>@endif
        </div>
    @endif

    <form method="POST" action="{{ route('chain.transport.store') }}" class="f2f-card p-5 sm:p-6 space-y-6">
        @csrf

        <section>
            <div class="border-b border-stone-200 pb-3 mb-4">
                <h2 class="text-base font-bold text-stone-900">1. Phân công nguồn lực</h2>
                <p class="text-xs text-stone-500">Chỉ hiển thị tài xế chưa có chuyến và xe lạnh sẵn sàng có IoT hoạt động.</p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="f2f-label" for="tai_xe_id">Tài xế phụ trách <span class="text-rose-600">*</span></label>
                    <select class="f2f-input" name="tai_xe_id" id="tai_xe_id" required>
                        <option value="">-- Chọn tài xế --</option>
                        @foreach($drivers as $driver)
                            <option value="{{ $driver->tai_khoan_id }}" @selected(old('tai_xe_id') == $driver->tai_khoan_id)>
                                {{ $driver->ho_ten }} · {{ $driver->so_dien_thoai ?: $driver->email }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="f2f-label" for="phuong_tien_id">Xe lạnh <span class="text-rose-600">*</span></label>
                    <select class="f2f-input" name="phuong_tien_id" id="phuong_tien_id" required>
                        <option value="">-- Chọn phương tiện --</option>
                        @foreach($vehicles as $vehicle)
                            <option value="{{ $vehicle->phuong_tien_id }}" @selected(old('phuong_tien_id') == $vehicle->phuong_tien_id)>
                                {{ $vehicle->bien_so }} · {{ $vehicle->loai_xe }} · {{ number_format($vehicle->tai_trong, 0) }} {{ $vehicle->don_vi_tai_trong }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
        </section>

        <section>
            <div class="border-b border-stone-200 pb-3 mb-4">
                <h2 class="text-base font-bold text-stone-900">2. Lộ trình & lịch dự kiến</h2>
                <p class="text-xs text-stone-500">Mã chuyến được hệ thống cấp tự động sau khi lưu.</p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="f2f-label" for="diem_di">Điểm xuất phát <span class="text-rose-600">*</span></label>
                    <input class="f2f-input" id="diem_di" name="diem_di" value="{{ old('diem_di') }}" required maxlength="255" placeholder="Ví dụ: Kho lạnh Farm2Fork Đà Lạt">
                </div>
                <div>
                    <label class="f2f-label" for="diem_den">Điểm giao hàng <span class="text-rose-600">*</span></label>
                    <input class="f2f-input" id="diem_den" name="diem_den" value="{{ old('diem_den') }}" required maxlength="255" placeholder="Ví dụ: Trung tâm phân phối TP.HCM">
                </div>
                <div>
                    <label class="f2f-label" for="thoi_gian_xuat_phat">Dự kiến xuất phát <span class="text-rose-600">*</span></label>
                    <input class="f2f-input" type="datetime-local" id="thoi_gian_xuat_phat" name="thoi_gian_xuat_phat" value="{{ old('thoi_gian_xuat_phat', now()->addHour()->format('Y-m-d\TH:i')) }}" required>
                </div>
                <div>
                    <label class="f2f-label" for="thoi_gian_giao_du_kien">Dự kiến giao hàng <span class="text-rose-600">*</span></label>
                    <input class="f2f-input" type="datetime-local" id="thoi_gian_giao_du_kien" name="thoi_gian_giao_du_kien" value="{{ old('thoi_gian_giao_du_kien', now()->addHours(6)->format('Y-m-d\TH:i')) }}" required>
                </div>
                <div class="md:col-span-2">
                    <label class="f2f-label" for="ghi_chu">Ghi chú điều phối</label>
                    <textarea class="f2f-input" id="ghi_chu" name="ghi_chu" rows="3" maxlength="1000" placeholder="Yêu cầu nhiệt độ, thời gian nhận hàng hoặc lưu ý tuyến đường...">{{ old('ghi_chu') }}</textarea>
                </div>
            </div>
        </section>

        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-950">
            Sau khi lập chuyến: <strong>Kho lạnh chọn chuyến khi xuất kiện SSCC</strong> → chuyến chuyển sang <strong>Sẵn sàng</strong> → tài xế bấm <strong>Bắt đầu chuyến</strong>.
        </div>

        <div class="flex justify-end gap-3 border-t border-stone-200 pt-5">
            <a href="{{ route('chain.transport.index') }}" class="f2f-btn-secondary">Hủy</a>
            <button type="submit" class="f2f-btn-primary" @disabled($drivers->isEmpty() || $vehicles->isEmpty())>Lập chuyến & phân công</button>
        </div>
    </form>
</div>
@endsection
