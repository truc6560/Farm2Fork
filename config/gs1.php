<?php

return [
    /*
    |--------------------------------------------------------------------------
    | GS1 Company Prefix
    |--------------------------------------------------------------------------
    |
    | Mã tiền tố doanh nghiệp GS1 (GS1 Company Prefix) do Tổ chức GS1 quốc gia
    | cấp phép (ví dụ GS1 Việt Nam có mã quốc gia 893...).
    | Độ dài tiền tố được cấp có thể từ 4 đến 12 chữ số.
    |
    | CẢNH BÁO PHÁP LÝ: Doanh nghiệp không được tự ý phát hành mã SSCC thương mại
    | ra thị trường khi chưa được cấp mã tiền tố chính thức từ GS1.
    |
    */
    'company_prefix' => env('GS1_COMPANY_PREFIX', '8938500'),

    /*
    |--------------------------------------------------------------------------
    | Trạng thái Môi trường Thử nghiệm (Demo Mode)
    |--------------------------------------------------------------------------
    |
    | true: Đang sử dụng mã tiền tố thử nghiệm/demo. Nhãn in logistics sẽ hiển thị
    |       rõ trạng thái thử nghiệm để tránh nhầm lẫn với mã thương mại.
    | false: Doanh nghiệp đã được cấp mã GS1 chính thức và hoạt động thực tế.
    |
    */
    'is_demo' => env('GS1_IS_DEMO_PREFIX', true),

    /*
    |--------------------------------------------------------------------------
    | Chữ số mở rộng mặc định (Extension Digit: 0 - 9)
    |--------------------------------------------------------------------------
    |
    | 0: Đơn vị logistics hỗn hợp / mặc định (thùng carton, kiện gom)
    | 1: Pallet tiêu chuẩn
    | 2..9: Phân loại theo nhu cầu quản trị logistics nội bộ
    |
    */
    'default_extension_digit' => (int) env('GS1_DEFAULT_EXTENSION_DIGIT', 0),

    /*
    |--------------------------------------------------------------------------
    | Chiến lược cấp số tham chiếu (Serial Reference Strategy)
    |--------------------------------------------------------------------------
    |
    | 'sequential': Cấp số tăng dần an toàn theo cơ sở dữ liệu (khuyến nghị cho GS1)
    | 'random_safe': Sinh số ngẫu nhiên có kiểm tra trùng lặp và checksum
    |
    */
    'serial_strategy' => env('GS1_SERIAL_STRATEGY', 'sequential'),
];

