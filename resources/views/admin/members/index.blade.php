@extends('layouts.dashboard', ['title' => 'Quản lý thành viên', 'breadcrumb' => 'Farm2Fork · Quản trị viên'])

@section('content')
<div class="f2f-card">
    <div class="f2f-card-toolbar">
        <div>
            <h2 class="f2f-card-title">Thành viên & Lời mời tham gia</h2>
            <p class="f2f-subtitle">Quản lý nhân sự trong chuỗi Farm2Fork, gửi lời mời bằng email và phân quyền theo phạm vi vận hành.</p>
        </div>
        <div>
            <button type="button" class="f2f-btn-primary" onclick="openInviteModal()" style="display:inline-flex;align-items:center;gap:6px;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                Mời thành viên mới
            </button>
        </div>
    </div>

    @if(session('status'))
        <div class="f2f-alert f2f-alert-success" style="margin-bottom:16px;">
            {{ session('status') }}
        </div>
    @endif

    @if(session('latest_invitation_url'))
        <div style="background:#ecfdf5;border:1px solid #a7f3d0;border-left:4px solid #059669;border-radius:8px;padding:16px;margin-bottom:20px;">
            <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:8px;">
                <div style="font-weight:700;color:#065f46;display:flex;align-items:center;gap:6px;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/>
                        <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/>
                    </svg>
                    Liên kết mời thành viên cho: {{ session('latest_invitation_email') }}
                </div>
                <span style="font-size:12px;background:#d1fae5;color:#065f46;padding:2px 8px;border-radius:12px;font-weight:600;">
                    Hiệu lực 7 ngày
                </span>
            </div>
            <p style="font-size:13px;color:#047857;margin:0 0 10px 0;">
                Môi trường thử nghiệm / Khi chưa cấu hình SMTP: Bạn có thể sao chép liên kết dưới đây và mở trực tiếp trên trình duyệt để demo quy trình nhận lời mời.
            </p>
            <div style="display:flex;gap:8px;flex-wrap:wrap;">
                <input type="text" readonly id="latest-invite-input" value="{{ session('latest_invitation_url') }}" class="f2f-input" style="flex:1;min-width:260px;background:#ffffff;font-size:13px;font-family:monospace;color:#0f172a;">
                <button type="button" class="f2f-btn-primary" onclick="copyInputText('latest-invite-input', this)" style="white-space:nowrap;">
                    📋 Sao chép liên kết
                </button>
            </div>
        </div>
    @endif

    @if($errors->any())
        <div class="f2f-alert f2f-alert-error" style="margin-bottom:16px;">
            <ul style="margin:0;padding-left:18px;">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Tabs Navigation -->
    <div style="display:flex;gap:8px;border-bottom:1px solid #e2e8f0;margin-bottom:20px;overflow-x:auto;">
        <button type="button" class="f2f-tab-btn {{ $activeTab === 'members' ? 'active' : '' }}" onclick="switchTab('members')" id="tab-btn-members" style="padding:10px 18px;font-weight:600;font-size:14px;border:none;background:transparent;cursor:pointer;border-bottom:3px solid transparent;display:flex;align-items:center;gap:6px;">
            Thành viên hiện có
            <span class="f2f-tag" style="font-size:11px;padding:1px 6px;">{{ $members->total() }}</span>
        </button>
        <button type="button" class="f2f-tab-btn {{ $activeTab === 'invitations' ? 'active' : '' }}" onclick="switchTab('invitations')" id="tab-btn-invitations" style="padding:10px 18px;font-weight:600;font-size:14px;border:none;background:transparent;cursor:pointer;border-bottom:3px solid transparent;display:flex;align-items:center;gap:6px;">
            Lời mời tham gia
            <span class="f2f-tag" style="font-size:11px;padding:1px 6px;">{{ $invitations->total() }}</span>
        </button>
    </div>

    <!-- TAB 1: THÀNH VIÊN HIỆN CÓ -->
    <div id="tab-panel-members" style="{{ $activeTab === 'members' ? '' : 'display:none;' }}">
        <!-- Filters -->
        <form method="GET" class="f2f-filter-box" style="margin-bottom:16px;">
            <input type="hidden" name="tab" value="members">
            <div class="f2f-filter-grid">
                <div>
                    <input class="f2f-input" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Tìm theo tên, email, SĐT...">
                </div>
                <div>
                    <select class="f2f-input" name="vai_tro_id">
                        <option value="">Tất cả vai trò</option>
                        @foreach ($roles as $r)
                            <option value="{{ $r->vai_tro_id }}" @selected((string)($filters['vai_tro_id'] ?? '') === (string)$r->vai_tro_id)>{{ $r->ten_vai_tro }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <select class="f2f-input" name="trang_thai">
                        <option value="">Tất cả trạng thái</option>
                        @foreach ($memberStatuses as $code => $label)
                            <option value="{{ $code }}" @selected(($filters['trang_thai'] ?? '') === $code)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="f2f-filter-actions">
                    <button class="f2f-btn-primary" type="submit">Lọc</button>
                    @if(!empty($filters['q']) || !empty($filters['vai_tro_id']) || !empty($filters['trang_thai']))
                        <a href="{{ route('admin.members.index', ['tab' => 'members']) }}" class="f2f-btn-ghost">Xóa lọc</a>
                    @endif
                </div>
            </div>
        </form>

        <div class="f2f-table-wrap">
            <table class="f2f-table">
                <thead>
                    <tr>
                        <th>Thành viên</th>
                        <th>Email & SĐT</th>
                        <th>Vai trò</th>
                        <th>Đơn vị / Phạm vi</th>
                        <th>Trạng thái</th>
                        <th>Ngày tạo</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($members as $m)
                    <tr>
                        <td>
                            <div style="display:flex;align-items:center;gap:10px;">
                                <div style="width:34px;height:34px;border-radius:50%;background:#1b4d3e;color:#ffffff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:14px;flex-shrink:0;">
                                    {{ mb_substr($m->ho_ten ?? 'U', 0, 1) }}
                                </div>
                                <div>
                                    <strong style="color:#0f172a;">{{ $m->ho_ten }}</strong>
                                    @if($m->tai_khoan_id === auth()->id())
                                        <span style="font-size:11px;background:#f1f5f9;color:#475569;padding:1px 5px;border-radius:4px;margin-left:4px;">Bạn</span>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td>
                            <div style="font-size:13px;color:#0f172a;">{{ $m->email }}</div>
                            @if($m->so_dien_thoai)
                                <div style="font-size:12px;color:#64748b;">{{ $m->so_dien_thoai }}</div>
                            @endif
                        </td>
                        <td>
                            <span class="f2f-tag" style="font-weight:600;">
                                {{ $m->vaiTro->ten_vai_tro ?? '—' }}
                            </span>
                        </td>
                        <td>
                            <div style="font-size:13px;">
                                <strong>{{ $m->toChuc->ten_to_chuc ?? '—' }}</strong>
                            </div>
                            @if($m->khoLanh && $m->khoLanh->isNotEmpty())
                                <div style="font-size:12px;color:#047857;margin-top:2px;">
                                    ❄️ Kho: {{ $m->khoLanh->pluck('ten_kho')->join(', ') }}
                                </div>
                            @endif
                            @php
                                $assignedZones = $m->vungTrong && $m->vungTrong->isNotEmpty() ? $m->vungTrong : $m->vungTrongPhuTrach;
                            @endphp
                            @if($assignedZones && $assignedZones->isNotEmpty())
                                <div style="font-size:12px;color:#047857;margin-top:2px;">
                                    🌱 Vùng: {{ $assignedZones->pluck('ten_vung_trong')->join(', ') }}
                                </div>
                            @endif
                        </td>
                        <td>
                            @if($m->trang_thai === \App\Support\AccountStatus::DANG_HOAT_DONG)
                                <span class="f2f-badge-success" style="display:inline-block;padding:2px 8px;border-radius:999px;font-size:12px;font-weight:600;background:#ecfdf5;color:#065f46;">
                                    Hoạt động
                                </span>
                            @elseif($m->trang_thai === \App\Support\AccountStatus::KHOA)
                                <span class="f2f-badge-danger" style="display:inline-block;padding:2px 8px;border-radius:999px;font-size:12px;font-weight:600;background:#fef2f2;color:#991b1b;">
                                    Bị khóa
                                </span>
                            @else
                                <span class="f2f-badge-warning" style="display:inline-block;padding:2px 8px;border-radius:999px;font-size:12px;font-weight:600;background:#fffbeb;color:#92400e;">
                                    {{ $memberStatuses[$m->trang_thai] ?? $m->trang_thai }}
                                </span>
                            @endif
                        </td>
                        <td style="font-size:13px;color:#64748b;">
                            {{ $m->created_at ? $m->created_at->format('d/m/Y') : '—' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="text-align:center;padding:32px;color:#64748b;">
                            Không tìm thấy thành viên nào phù hợp bộ lọc.
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        @if($members->hasPages())
            <div style="margin-top:16px;">
                {{ $members->links() }}
            </div>
        @endif
    </div>

    <!-- TAB 2: LỜI MỜI THAM GIA -->
    <div id="tab-panel-invitations" style="{{ $activeTab === 'invitations' ? '' : 'display:none;' }}">
        <!-- Filters -->
        <form method="GET" class="f2f-filter-box" style="margin-bottom:16px;">
            <input type="hidden" name="tab" value="invitations">
            <div class="f2f-filter-grid">
                <div>
                    <input class="f2f-input" name="inv_q" value="{{ $filters['inv_q'] ?? '' }}" placeholder="Tìm theo email lời mời...">
                </div>
                <div>
                    <select class="f2f-input" name="inv_status">
                        <option value="">Tất cả trạng thái lời mời</option>
                        @foreach ($invitationStatuses as $code => $label)
                            <option value="{{ $code }}" @selected(($filters['inv_status'] ?? '') === $code)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="f2f-filter-actions">
                    <button class="f2f-btn-primary" type="submit">Lọc</button>
                    @if(!empty($filters['inv_q']) || !empty($filters['inv_status']))
                        <a href="{{ route('admin.members.index', ['tab' => 'invitations']) }}" class="f2f-btn-ghost">Xóa lọc</a>
                    @endif
                </div>
            </div>
        </form>

        <div class="f2f-table-wrap">
            <table class="f2f-table">
                <thead>
                    <tr>
                        <th>Email người nhận</th>
                        <th>Vai trò</th>
                        <th>Phạm vi phân công</th>
                        <th>Trạng thái</th>
                        <th>Hạn lời mời</th>
                        <th>Người gửi</th>
                        <th style="text-align:right;">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($invitations as $inv)
                    <tr>
                        <td>
                            <strong style="color:#0f172a;font-size:14px;">{{ $inv->email }}</strong>
                        </td>
                        <td>
                            <span class="f2f-tag" style="font-weight:600;">
                                {{ $inv->vaiTro->ten_vai_tro ?? '—' }}
                            </span>
                        </td>
                        <td style="font-size:13px;color:#334155;">
                            {{ $inv->pham_vi_chi_tiet }}
                        </td>
                        <td>
                            @php $badge = $inv->trang_thai_badge; @endphp
                            @if($inv->trang_thai === \App\Models\LoiMoi::TRANG_THAI_DA_CHAP_NHAN)
                                <span style="display:inline-block;padding:2px 8px;border-radius:999px;font-size:12px;font-weight:600;background:#ecfdf5;color:#065f46;">
                                    ✓ Đã tham gia
                                </span>
                            @elseif($inv->trang_thai === \App\Models\LoiMoi::TRANG_THAI_DA_THU_HOI)
                                <span style="display:inline-block;padding:2px 8px;border-radius:999px;font-size:12px;font-weight:600;background:#fef2f2;color:#991b1b;">
                                    ✕ Đã thu hồi
                                </span>
                            @elseif($inv->isExpired())
                                <span style="display:inline-block;padding:2px 8px;border-radius:999px;font-size:12px;font-weight:600;background:#f1f5f9;color:#64748b;">
                                    ⏱️ Hết hạn
                                </span>
                            @else
                                <span style="display:inline-block;padding:2px 8px;border-radius:999px;font-size:12px;font-weight:600;background:#fffbeb;color:#92400e;">
                                    ⏳ Đang chờ
                                </span>
                            @endif
                        </td>
                        <td style="font-size:13px;color:{{ $inv->isExpired() ? '#dc2626' : '#475569' }};">
                            {{ $inv->het_han ? $inv->het_han->format('H:i d/m/Y') : '—' }}
                        </td>
                        <td style="font-size:12px;color:#64748b;">
                            <div>{{ $inv->nguoiMoi->ho_ten ?? 'Hệ thống' }}</div>
                            <div>{{ $inv->created_at ? $inv->created_at->format('d/m/Y') : '' }}</div>
                        </td>
                        <td style="text-align:right;">
                            <div style="display:inline-flex;gap:6px;align-items:center;justify-content:flex-end;">
                                @if($inv->trang_thai === \App\Models\LoiMoi::TRANG_THAI_CHO && !$inv->isExpired())
                                    <button type="button" class="f2f-btn-ghost" onclick="copyInvitationLink({{ $inv->id }}, '{{ $inv->email }}', this)" title="Sao chép liên kết mời để gửi trực tiếp" style="padding:4px 10px;font-size:12px;">
                                        📋 Sao chép link
                                    </button>
                                    <form method="POST" action="{{ route('admin.members.invitations.resend', $inv->id) }}" style="display:inline;">
                                        @csrf
                                        <button type="submit" class="f2f-btn-ghost" title="Tạo mã mới và gửi lại email" style="padding:4px 10px;font-size:12px;">
                                            🔄 Gửi lại
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.members.invitations.revoke', $inv->id) }}" style="display:inline;" onsubmit="return confirm('Bạn có chắc chắn muốn thu hồi lời mời này? Người nhận sẽ không thể sử dụng liên kết cũ nữa.');">
                                        @csrf
                                        <button type="submit" class="f2f-btn-ghost" style="padding:4px 10px;font-size:12px;color:#dc2626;" title="Vô hiệu hóa lời mời">
                                            ✕ Thu hồi
                                        </button>
                                    </form>
                                @elseif($inv->trang_thai === \App\Models\LoiMoi::TRANG_THAI_HET_HAN || $inv->trang_thai === \App\Models\LoiMoi::TRANG_THAI_DA_THU_HOI)
                                    <form method="POST" action="{{ route('admin.members.invitations.resend', $inv->id) }}" style="display:inline;">
                                        @csrf
                                        <button type="submit" class="f2f-btn-ghost" title="Cấp liên kết mới và mở lại lời mời (7 ngày)" style="padding:4px 10px;font-size:12px;">
                                            🔄 Gửi lại lời mời
                                        </button>
                                    </form>
                                @else
                                    <span style="font-size:12px;color:#059669;font-weight:600;">Hoàn tất</span>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="text-align:center;padding:32px;color:#64748b;">
                            Chưa có lời mời nào được gửi. Bấm <strong>"Mời thành viên mới"</strong> để gửi lời mời đầu tiên!
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        @if($invitations->hasPages())
            <div style="margin-top:16px;">
                {{ $invitations->links() }}
            </div>
        @endif
    </div>
</div>

<!-- MODAL: MỜI THÀNH VIÊN -->
<div id="invite-modal" style="display:none;position:fixed;inset:0;background:rgba(15,23,42,0.6);z-index:9999;align-items:center;justify-content:center;padding:16px;">
    <div style="background:#ffffff;border-radius:12px;max-width:540px;width:100%;box-shadow:0 20px 25px -5px rgba(0,0,0,0.1), 0 8px 10px -6px rgba(0,0,0,0.1);max-height:90vh;overflow-y:auto;">
        <div style="display:flex;align-items:center;justify-content:space-between;padding:18px 24px;border-bottom:1px solid #e2e8f0;">
            <h3 style="margin:0;font-size:18px;font-weight:700;color:#0f172a;">Mời thành viên mới</h3>
            <button type="button" onclick="closeInviteModal()" style="border:none;background:transparent;cursor:pointer;color:#64748b;font-size:20px;padding:4px;">✕</button>
        </div>

        <form method="POST" action="{{ route('admin.members.invite') }}" style="padding:24px;">
            @csrf

            <div style="margin-bottom:16px;">
                <label class="f2f-label" for="invite_email">Địa chỉ email người nhận <span style="color:#dc2626;">*</span></label>
                <input type="email" id="invite_email" name="email" class="f2f-input" placeholder="vd: nguyenvanan@farm2fork.vn" required>
                <p style="font-size:12px;color:#64748b;margin-top:4px;">Hệ thống sẽ gửi email có liên kết kích hoạt đến địa chỉ này.</p>
            </div>

            <div style="margin-bottom:16px;">
                <label class="f2f-label" for="invite_vai_tro">Vai trò chỉ định <span style="color:#dc2626;">*</span></label>
                <select id="invite_vai_tro" name="vai_tro_id" class="f2f-input" required onchange="handleRoleChange(this.options[this.selectedIndex].getAttribute('data-code'))">
                    <option value="">-- Chọn vai trò --</option>
                    @foreach($roles as $r)
                        <option value="{{ $r->vai_tro_id }}" data-code="{{ $r->ma_vai_tro }}">
                            {{ $r->ten_vai_tro }} ({{ $r->ma_vai_tro }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div style="margin-bottom:16px;">
                <label class="f2f-label" for="invite_to_chuc">Tổ chức / Đơn vị thành viên</label>
                <select id="invite_to_chuc" name="to_chuc_id" class="f2f-input">
                    @foreach($organizations as $org)
                        <option value="{{ $org->to_chuc_id }}" @selected($org->to_chuc_id == auth()->user()->to_chuc_id)>
                            {{ $org->ten_to_chuc }} ({{ $org->loai_to_chuc ?? 'Tổ chức' }})
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Dynamic Scope 1: Kho lạnh (khi chọn NV_KHO_LANH) -->
            <div id="scope-warehouse" style="display:none;margin-bottom:16px;background:#f0fdf4;padding:12px;border-radius:8px;border:1px solid #bbf7d0;">
                <label class="f2f-label" for="invite_kho_lanh" style="color:#166534;">❄️ Kho lạnh phụ trách (tùy chọn)</label>
                <select id="invite_kho_lanh" name="kho_lanh_id" class="f2f-input">
                    <option value="">-- Chọn kho lạnh làm việc --</option>
                    @foreach($warehouses as $wh)
                        <option value="{{ $wh->kho_lanh_id }}">{{ $wh->ten_kho }} ({{ $wh->dia_chi }})</option>
                    @endforeach
                </select>
                <p style="font-size:12px;color:#15803d;margin-top:4px;">Nhân viên sẽ được tự động phân công vào kho lạnh này sau khi chấp nhận lời mời.</p>
            </div>

            <!-- Dynamic Scope 2: Vùng trồng (khi chọn NGUOI_SAN_XUAT) -->
            <div id="scope-planting-zone" style="display:none;margin-bottom:16px;background:#f0fdf4;padding:14px;border-radius:8px;border:1px solid #bbf7d0;">
                <label class="f2f-label" style="color:#166534;margin-bottom:6px;display:block;">
                    🌱 Phân công vùng trồng phụ trách (Tùy chọn - có thể chọn nhiều vùng hoặc để trống)
                </label>
                <p style="font-size:12px;color:#15803d;margin:0 0 10px 0;">
                    Chọn một hoặc nhiều vùng trồng giao cho người sản xuất. Nếu để trống, người sản xuất có thể tự khai báo vùng trồng mới sau khi kích hoạt tài khoản.
                </p>
                <div style="max-height:160px;overflow-y:auto;border:1px solid #cbd5e1;border-radius:6px;background:#ffffff;padding:8px 12px;display:flex;flex-direction:column;gap:6px;">
                    @forelse($plantingZones as $vz)
                        <label style="display:flex;align-items:center;gap:8px;font-size:13px;color:#1e293b;cursor:pointer;padding:3px 0;">
                            <input type="checkbox" name="vung_trong_ids[]" value="{{ $vz->vung_trong_id }}">
                            <span>
                                <strong>{{ $vz->ten_vung_trong }}</strong>
                                <span style="color:#64748b;font-size:12px;">({{ $vz->cay_trong_chinh ?? 'Nông sản' }} - {{ $vz->dia_chi }})</span>
                            </span>
                        </label>
                    @empty
                        <span style="font-size:12px;color:#64748b;">Chưa có vùng trồng nào trên hệ thống.</span>
                    @endforelse
                </div>
            </div>

            <!-- Dynamic Scope 3: Phương tiện (khi chọn TAI_XE) -->
            <div id="scope-vehicle" style="display:none;margin-bottom:16px;background:#f0fdf4;padding:12px;border-radius:8px;border:1px solid #bbf7d0;">
                <label class="f2f-label" for="invite_phuong_tien" style="color:#166534;">🚚 Phương tiện vận chuyển chính (tùy chọn)</label>
                <select id="invite_phuong_tien" name="phuong_tien_id" class="f2f-input">
                    <option value="">-- Chọn phương tiện --</option>
                    @foreach($vehicles as $vh)
                        <option value="{{ $vh->phuong_tien_id }}">{{ $vh->bien_so }} ({{ $vh->loai_xe }})</option>
                    @endforeach
                </select>
            </div>

            <div style="background:#f8fafc;padding:12px;border-radius:8px;margin-bottom:20px;font-size:13px;color:#475569;line-height:1.5;">
                ⏱️ <strong>Chính sách mời:</strong> Lời mời có hiệu lực trong <strong>7 ngày</strong>. Người được mời sẽ mở đường dẫn bí mật, tự thiết lập mật khẩu của mình và tài khoản được tạo ngay lập tức.
            </div>

            <div style="display:flex;justify-content:flex-end;gap:10px;">
                <button type="button" class="f2f-btn-ghost" onclick="closeInviteModal()">Hủy</button>
                <button type="submit" class="f2f-btn-primary">Gửi lời mời</button>
            </div>
        </form>
    </div>
</div>

<!-- Toast notification element -->
<div id="copy-toast" style="display:none;position:fixed;bottom:24px;right:24px;background:#0f172a;color:#ffffff;padding:12px 20px;border-radius:8px;font-size:14px;box-shadow:0 10px 15px -3px rgba(0,0,0,0.2);z-index:10000;display:none;align-items:center;gap:8px;">
    <span>✓</span> <span id="toast-message">Đã sao chép liên kết!</span>
</div>

<style>
.f2f-tab-btn.active {
    color: #1b4d3e !important;
    border-bottom-color: #1b4d3e !important;
}
.f2f-tab-btn:not(.active) {
    color: #64748b;
}
.f2f-tab-btn:hover {
    color: #0f172a;
}
</style>

<script>
function switchTab(tab) {
    document.getElementById('tab-panel-members').style.display = tab === 'members' ? '' : 'none';
    document.getElementById('tab-panel-invitations').style.display = tab === 'invitations' ? '' : 'none';

    document.getElementById('tab-btn-members').classList.toggle('active', tab === 'members');
    document.getElementById('tab-btn-invitations').classList.toggle('active', tab === 'invitations');

    // Update URL query string without reloading
    const url = new URL(window.location);
    url.searchParams.set('tab', tab);
    window.history.replaceState({}, '', url);
}

function openInviteModal() {
    const modal = document.getElementById('invite-modal');
    modal.style.display = 'flex';
    document.getElementById('invite_email').focus();
}

function closeInviteModal() {
    document.getElementById('invite-modal').style.display = 'none';
}

function handleRoleChange(roleCode) {
    document.getElementById('scope-warehouse').style.display = (roleCode === 'NV_KHO_LANH') ? 'block' : 'none';
    document.getElementById('scope-planting-zone').style.display = (roleCode === 'NGUOI_SAN_XUAT') ? 'block' : 'none';
    document.getElementById('scope-vehicle').style.display = (roleCode === 'TAI_XE') ? 'block' : 'none';
}

function showToast(message) {
    const toast = document.getElementById('copy-toast');
    document.getElementById('toast-message').innerText = message;
    toast.style.display = 'flex';
    setTimeout(() => {
        toast.style.display = 'none';
    }, 3000);
}

function copyInputText(inputId, btn) {
    const input = document.getElementById(inputId);
    input.select();
    input.setSelectionRange(0, 99999);
    navigator.clipboard.writeText(input.value).then(() => {
        showToast('Đã sao chép liên kết vào bộ nhớ tạm!');
        if (btn) {
            const originalText = btn.innerHTML;
            btn.innerHTML = '✓ Đã sao chép!';
            setTimeout(() => { btn.innerHTML = originalText; }, 2000);
        }
    }).catch(err => {
        // Fallback
        document.execCommand('copy');
        showToast('Đã sao chép liên kết!');
    });
}

function copyInvitationLink(id, email, btn) {
    if (btn) {
        btn.disabled = true;
        btn.innerText = 'Đang lấy link...';
    }

    fetch('{{ url("admin/members/invitations") }}/' + id + '/link', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        }
    })
    .then(res => res.json())
    .then(data => {
        if (btn) {
            btn.disabled = false;
            btn.innerText = '📋 Sao chép link';
        }
        if (data.success && data.url) {
            navigator.clipboard.writeText(data.url).then(() => {
                showToast(data.message || 'Đã sao chép liên kết mời cho ' + email + '!');
            }).catch(() => {
                prompt('Sao chép liên kết bên dưới:', data.url);
            });
        } else {
            alert(data.message || 'Không thể lấy liên kết mời.');
        }
    })
    .catch(err => {
        if (btn) {
            btn.disabled = false;
            btn.innerText = '📋 Sao chép link';
        }
        alert('Lỗi khi lấy liên kết mời.');
    });
}

// Close modal when clicking outside
window.addEventListener('click', function(e) {
    const modal = document.getElementById('invite-modal');
    if (e.target === modal) {
        closeInviteModal();
    }
});
</script>
@endsection

