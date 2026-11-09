<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Cấu hình MQTT Broker nội bộ (Local Mosquitto)
    |--------------------------------------------------------------------------
    |
    | Kết nối cục bộ qua localhost/127.0.0.1 để bảo mật dữ liệu chuỗi cung ứng,
    | tuyệt đối không sử dụng public broker bên ngoài.
    |
    */
    'host' => env('MQTT_HOST', '127.0.0.1'),
    'port' => (int) env('MQTT_PORT', 1883),
    'client_id' => env('MQTT_CLIENT_ID', 'farm2fork_backend_' . getmypid()),
    'username' => env('MQTT_USERNAME', null),
    'password' => env('MQTT_PASSWORD', null),

    /*
    | Topic mặc định để lắng nghe cảm biến nhiệt độ & độ ẩm
    | Cấu trúc: farm2fork/kho/{ma_thiet_bi}/telemetry
    |           farm2fork/xe/{ma_thiet_bi}/telemetry
    */
    'topic' => env('MQTT_TOPIC', 'farm2fork/#'),

    'qos' => (int) env('MQTT_QOS', 0),
    'timeout' => (int) env('MQTT_TIMEOUT', 10),
];

