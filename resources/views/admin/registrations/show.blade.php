@extends('layouts.dashboard', ['title' => 'Hồ sơ: ' . $registration['application_code'], 'breadcrumb' => 'Farm2Fork · Quản trị viên · Chi tiết hồ sơ'])

@section('content')
<div class="f2f-card">
    <div class="f2f-card-toolbar">
        <div>
            <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
                <h2 class="f2f-card-title">Hồ sơ: <code>{{ $registration['application_code'] }}</code></h2>
                <span class="f2f-badge {{ in_array($registration['status'], ['APPROVED', 'DA_PHE_DUYET']) ? 'f2f-badge-success' : (in_array($registration['status'], ['REJECTED', 'TU_CHOI']) ? 'f2f-badge-danger' : 'f2f-badge-warning') }}">
                    {{ $statusOptions[$registration['status']] ?? $registration['status'] }}
                </span>
            </div>
            <p class="f2f-subtitle" style="margin-top:4px">
                Cơ sở: <strong>{{ $registration['organization']['name'] }}</strong> ({{ $registration['organization']['type'] }})
            </p>
        </div>
        <div class="f2f-toolbar-actions">
            <a class="f2f-btn-ghost" href="{{ route('admin.registrations.index') }}">
                Danh sách hồ sơ
            </a>
        </div>
    </div>

    <!-- Organization details -->
    <div class="f2f-form-section">
        <h3 class="f2f-section-title">Thông tin cơ sở đăng ký</h3>
        <div class="f2f-info-grid" style="margin-top:0;padding-top:0;border-top:0">
            <div class="f2f-info-item">
                <span class="f2f-info-label">Tên cơ sở</span>
                <strong class="f2f-info-val">{{ $registration['organization']['name'] }}</strong>
            </div>
            <div class="f2f-info-item">
                <span class="f2f-info-label">Loại hình tổ chức</span>
                <strong class="f2f-info-val">{{ $registration['organization']['type'] }}</strong>
            </div>
            <div class="f2f-info-item">
                <span class="f2f-info-label">Người đại diện</span>
                <strong class="f2f-info-val">{{ $registration['organization']['representative'] }}</strong>
            </div>
            <div class="f2f-info-item">
                <span class="f2f-info-label">Định danh pháp lý (MST/CCCD)</span>
                <strong class="f2f-info-val">{{ $registration['organization']['legal_id'] }}</strong>
            </div>
            <div class="f2f-info-item">
                <span class="f2f-info-label">Số điện thoại liên hệ</span>
                <strong class="f2f-info-val">{{ $registration['organization']['contact_phone'] }}</strong>
            </div>
            <div class="f2f-info-item">
                <span class="f2f-info-label">Email liên hệ</span>
                <strong class="f2f-info-val">{{ $registration['organization']['contact_email'] }}</strong>
            </div>
            <div class="f2f-info-item f2f-col-span-2">
                <span class="f2f-info-label">Địa chỉ trụ sở / cơ sở</span>
                <span class="f2f-info-val">{{ $registration['organization']['address'] }}</span>
            </div>
            @if(!empty($registration['organization']['cultivation_region']))
                <div class="f2f-info-item f2f-col-span-2">
                    <span class="f2f-info-label">Khu vực vùng trồng</span>
                    <span class="f2f-info-val">{{ $registration['organization']['cultivation_region'] }}</span>
                </div>
            @endif
        </div>
    </div>

    <!-- Legal Documents -->
    <div class="f2f-form-section" style="margin-top:16px">
        <h3 class="f2f-section-title">Hồ sơ pháp lý & Giấy tờ chứng nhận</h3>
        <div class="f2f-form-grid">
            <div>
                <strong style="display:block;margin-bottom:8px;font-size:13.5px">Giấy tờ pháp lý:</strong>
                @forelse($registration['documents']['legal_documents'] as $doc)
                    <p style="margin:4px 0">
                        <a class="f2f-link" href="{{ route('admin.registrations.documents.download', [$registration['application_code'], $doc['id']]) }}">
                            {{ $doc['name'] }}
                        </a>
                    </p>
                @empty
                    <p class="f2f-muted">Không có tệp đính kèm.</p>
                @endforelse
            </div>
            <div>
                <strong style="display:block;margin-bottom:8px;font-size:13.5px">Chứng nhận liên quan (VietGAP / GlobalGAP...):</strong>
                @forelse($registration['documents']['certification_documents'] as $doc)
                    <p style="margin:4px 0">
                        <a class="f2f-link" href="{{ route('admin.registrations.documents.download', [$registration['application_code'], $doc['id']]) }}">
                            {{ $doc['name'] }}
                        </a>
                    </p>
                @empty
                    <p class="f2f-muted">Không có tệp đính kèm.</p>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Contact Verification -->
    <div class="f2f-form-section" style="margin-top:16px">
        <h3 class="f2f-section-title">Xác thực thông tin liên hệ</h3>
        @if(!empty($registration['contact_verification']['last_message']))
            <p style="margin-bottom:8px">{{ $registration['contact_verification']['last_message'] }}</p>
        @endif
        <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap">
            <span>
                Trạng thái xác thực: 
                <strong class="{{ ($registration['contact_verification']['verified'] ?? false) ? 'text-emerald-700' : 'text-amber-700' }}">
                    {{ ($registration['contact_verification']['verified'] ?? false) ? 'Đã xác thực' : 'Chưa xác thực' }}
                </strong>
            </span>
            <form method="POST" action="{{ route('admin.registrations.verify-contact', $registration['application_code']) }}">
                @csrf
                <button class="f2f-btn-ghost f2f-btn-sm" type="submit">Xác thực thông tin liên hệ</button>
            </form>
        </div>
    </div>

    <!-- Actions / Approval -->
    <div class="f2f-form-section" style="margin-top:16px">
        <h3 class="f2f-section-title">Thao tác duyệt hồ sơ</h3>
        <div class="f2f-form-grid">
            <form method="POST" action="{{ route('admin.registrations.approve', $registration['application_code']) }}" class="f2f-form-inline">
                @csrf
                <input class="f2f-input" name="note" placeholder="Ghi chú phê duyệt (tùy chọn)">
                <button class="f2f-btn-primary" type="submit">Phê duyệt hồ sơ</button>
            </form>

            <form method="POST" action="{{ route('admin.registrations.reject', $registration['application_code']) }}" class="f2f-form-inline">
                @csrf
                <input class="f2f-input" name="reason" placeholder="Lý do từ chối" required>
                <button class="f2f-btn-danger" type="submit">Từ chối hồ sơ</button>
            </form>

            <form method="POST" action="{{ route('admin.registrations.request-supplement', $registration['application_code']) }}" class="f2f-form-inline f2f-col-span-2">
                @csrf
                <input class="f2f-input" name="supplement_note" placeholder="Nội dung cần yêu cầu cơ sở bổ sung" required>
                <button class="f2f-btn-ghost" type="submit">Yêu cầu bổ sung hồ sơ</button>
            </form>
        </div>
    </div>

    <!-- History -->
    <div class="f2f-form-section" style="margin-top:16px">
        <h3 class="f2f-section-title">Lịch sử xử lý hồ sơ</h3>
        @if(!empty($registration['history']))
            <ul class="f2f-audit-chain" style="margin-top:0">
                @foreach($registration['history'] as $event)
                    <li>
                        <span>{{ $event['at'] }}</span> · 
                        <strong class="f2f-tag">{{ $event['status'] }}</strong> · 
                        <span>{{ $event['note'] }}</span> · 
                        <span class="f2f-muted">Thực hiện bởi: {{ $event['by'] }}</span>
                    </li>
                @endforeach
            </ul>
        @else
            <p class="f2f-muted">Chưa có lịch sử xử lý.</p>
        @endif
    </div>

    <!-- Account Provisioning -->
    <div class="f2f-form-section" style="margin-top:16px">
        <h3 class="f2f-section-title">Cấp tài khoản hệ thống sau phê duyệt</h3>
        <p class="f2f-field-hint" style="margin-bottom:12px">Lưu ý: Không cho phép cấp vai trò Quản trị viên (ADMIN) từ luồng duyệt đăng ký cơ sở.</p>
        <form method="POST" action="{{ route('admin.registrations.provision-account', $registration['application_code']) }}" class="f2f-form">
            @csrf
            <div class="f2f-form-grid">
                <div class="f2f-field">
                    <label class="f2f-label">Họ tên người nhận tài khoản</label>
                    <input class="f2f-input" name="full_name" value="{{ $registration['organization']['representative'] }}" required>
                </div>
                <div class="f2f-field">
                    <label class="f2f-label">Email đăng nhập</label>
                    <input class="f2f-input" type="email" name="email" value="{{ $registration['organization']['contact_email'] }}" required>
                </div>
                <div class="f2f-field">
                    <label class="f2f-label">Số điện thoại</label>
                    <input class="f2f-input" name="phone" value="{{ $registration['organization']['contact_phone'] }}">
                </div>
                <div class="f2f-field">
                    <label class="f2f-label">Vai trò được cấp</label>
                    <select class="f2f-input" name="vai_tro_id" required>
                        <option value="">Chọn vai trò</option>
                        <option value="2">Người sản xuất</option>
                        <option value="3">Nhân viên sơ chế/đóng gói</option>
                        <option value="4">QC</option>
                        <option value="5">Nhân viên kho lạnh</option>
                        <option value="6">Tài xế nội bộ</option>
                        <option value="7">Quản lý chuỗi cung ứng</option>
                    </select>
                </div>
            </div>
            <div style="margin-top:12px">
                <button class="f2f-btn-primary" type="submit">Cấp tài khoản & gửi thông tin</button>
            </div>
        </form>
    </div>
</div>
@endsection
