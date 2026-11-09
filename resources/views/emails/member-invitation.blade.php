<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lời mời tham gia Farm2Fork</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #f4f6f8; margin: 0; padding: 24px; color: #1e293b;">
    <table align="center" border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 580px; background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.06); border: 1px solid #e2e8f0;">
        <tr>
            <td style="background-color: #1b4d3e; padding: 28px 32px; text-align: center;">
                <h1 style="color: #ffffff; margin: 0; font-size: 22px; font-weight: 700; letter-spacing: 0.5px;">Farm2Fork</h1>
                <p style="color: #a7f3d0; margin: 6px 0 0 0; font-size: 13px;">Hệ thống Quản lý Chuỗi Cung ứng Lạnh & Truy xuất Nguồn gốc</p>
            </td>
        </tr>
        <tr>
            <td style="padding: 32px;">
                <h2 style="font-size: 18px; color: #0f172a; margin-top: 0; margin-bottom: 16px;">Xin chào,</h2>
                <p style="font-size: 15px; line-height: 1.6; color: #334155; margin-bottom: 20px;">
                    Bạn vừa nhận được lời mời tham gia hệ thống <strong>Farm2Fork</strong> từ Quản trị viên <strong>{{ $invitation->nguoiMoi->ho_ten ?? 'Hệ thống' }}</strong>.
                </p>

                <div style="background-color: #f8fafc; border-left: 4px solid #1b4d3e; padding: 14px 18px; margin-bottom: 24px; border-radius: 0 6px 6px 0;">
                    <p style="margin: 0 0 6px 0; font-size: 14px; color: #475569;">
                        <strong>Email đăng nhập:</strong> <span style="color: #0f172a;">{{ $invitation->email }}</span>
                    </p>
                    <p style="margin: 0 0 6px 0; font-size: 14px; color: #475569;">
                        <strong>Vai trò được chỉ định:</strong> <span style="color: #1b4d3e; font-weight: 600;">{{ $invitation->vaiTro->ten_vai_tro ?? 'Thành viên' }}</span>
                    </p>
                    @if($invitation->khoLanh)
                    <p style="margin: 0 0 6px 0; font-size: 14px; color: #475569;">
                        <strong>Kho lạnh phụ trách:</strong> {{ $invitation->khoLanh->ten_kho }}
                    </p>
                    @endif
                    @if($invitation->vungTrong)
                    <p style="margin: 0 0 6px 0; font-size: 14px; color: #475569;">
                        <strong>Vùng trồng:</strong> {{ $invitation->vungTrong->ten_vung_trong }}
                    </p>
                    @endif
                    @if($invitation->phuongTien)
                    <p style="margin: 0 0 6px 0; font-size: 14px; color: #475569;">
                        <strong>Phương tiện:</strong> {{ $invitation->phuongTien->bien_so }} ({{ $invitation->phuongTien->loai_xe }})
                    </p>
                    @endif
                    @if($invitation->toChuc)
                    <p style="margin: 0; font-size: 14px; color: #475569;">
                        <strong>Tổ chức / Đơn vị:</strong> {{ $invitation->toChuc->ten_to_chuc }}
                    </p>
                    @endif
                </div>

                <p style="font-size: 15px; line-height: 1.6; color: #334155; margin-bottom: 24px;">
                    Để hoàn tất đăng ký và đặt mật khẩu cho tài khoản, vui lòng bấm vào nút bên dưới:
                </p>

                <div style="text-align: center; margin-bottom: 28px;">
                    <a href="{{ $acceptUrl }}" style="display: inline-block; background-color: #1b4d3e; color: #ffffff; text-decoration: none; padding: 13px 32px; border-radius: 8px; font-weight: 600; font-size: 15px;">
                        Chấp nhận lời mời & Tạo mật khẩu
                    </a>
                </div>

                <p style="font-size: 13px; line-height: 1.5; color: #64748b; margin-bottom: 12px;">
                    Hoặc sao chép đường dẫn sau vào trình duyệt nếu nút bấm không mở được:
                </p>
                <p style="font-size: 12px; word-break: break-all; background-color: #f1f5f9; padding: 10px; border-radius: 6px; color: #334155; margin-bottom: 20px;">
                    <a href="{{ $acceptUrl }}" style="color: #1b4d3e; text-decoration: underline;">{{ $acceptUrl }}</a>
                </p>

                <div style="border-top: 1px solid #e2e8f0; padding-top: 18px; margin-top: 24px;">
                    <p style="font-size: 13px; color: #94a3b8; margin: 0 0 6px 0;">
                        ⏱️ Liên kết này có thời hạn sử dụng trong <strong>{{ $expiresDays }} ngày</strong> (hết hạn vào lúc {{ $invitation->het_han->format('H:i d/m/Y') }}).
                    </p>
                    <p style="font-size: 13px; color: #94a3b8; margin: 0;">
                        🔒 Nếu bạn không mong đợi nhận được thư này, bạn có thể yên tâm bỏ qua.
                    </p>
                </div>
            </td>
        </tr>
        <tr>
            <td style="background-color: #f8fafc; padding: 16px 32px; text-align: center; font-size: 12px; color: #94a3b8; border-top: 1px solid #e2e8f0;">
                © {{ date('Y') }} Farm2Fork. Hệ thống phục vụ vận hành chuỗi cung ứng lạnh nội bộ.
            </td>
        </tr>
    </table>
</body>
</html>

