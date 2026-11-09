<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đặt lại mật khẩu Farm2Fork</title>
</head>
<body style="font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Arial,sans-serif;background:#f4f6f8;margin:0;padding:24px;color:#1e293b;">
    <table align="center" border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width:580px;background:#fff;border-radius:12px;overflow:hidden;border:1px solid #e2e8f0;">
        <tr><td style="background:#1b4d3e;padding:28px 32px;text-align:center;color:#fff;">
            <h1 style="margin:0;font-size:22px;">Farm2Fork</h1>
            <p style="margin:6px 0 0;color:#a7f3d0;font-size:13px;">Quản lý chuỗi cung ứng và truy xuất nguồn gốc</p>
        </td></tr>
        <tr><td style="padding:32px;">
            <h2 style="margin:0 0 16px;color:#0f172a;font-size:19px;">Đặt lại mật khẩu</h2>
            <p style="font-size:15px;line-height:1.6;color:#334155;">Hệ thống nhận được yêu cầu đặt lại mật khẩu cho tài khoản <strong>{{ $recipientEmail }}</strong>.</p>
            <p style="font-size:15px;line-height:1.6;color:#334155;">Bấm nút bên dưới để tạo mật khẩu mới. Liên kết có hiệu lực trong 60 phút.</p>
            <div style="text-align:center;margin:28px 0;">
                <a href="{{ $resetUrl }}" style="display:inline-block;background:#1b4d3e;color:#fff;text-decoration:none;padding:13px 30px;border-radius:8px;font-weight:600;">Đặt lại mật khẩu</a>
            </div>
            <p style="font-size:13px;line-height:1.5;color:#64748b;">Nếu nút không mở được, sao chép liên kết sau vào trình duyệt:</p>
            <p style="font-size:12px;word-break:break-all;background:#f1f5f9;padding:10px;border-radius:6px;"><a href="{{ $resetUrl }}" style="color:#1b4d3e;">{{ $resetUrl }}</a></p>
            <p style="font-size:13px;color:#94a3b8;margin:20px 0 0;">Nếu bà không yêu cầu đặt lại mật khẩu, có thể bỏ qua email này.</p>
        </td></tr>
        <tr><td style="background:#f8fafc;padding:16px 32px;text-align:center;font-size:12px;color:#94a3b8;border-top:1px solid #e2e8f0;">© {{ date('Y') }} Farm2Fork</td></tr>
    </table>
</body>
</html>
