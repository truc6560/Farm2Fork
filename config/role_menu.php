<?php

return [
    'ADMIN' => [
        ['label' => 'Thành viên', 'route' => 'admin.members.index', 'active' => 'admin.members.*'],
        ['label' => 'Quản lý tài khoản', 'route' => 'admin.accounts.index', 'active' => 'admin.accounts.*'],
        ['label' => 'Tra cứu vùng trồng', 'route' => 'admin.planting-zones.index', 'active' => 'admin.planting-zones.*'],
        ['label' => 'Quản lý kho lạnh', 'route' => 'admin.warehouses.index', 'active' => 'admin.warehouses.*'],
        ['label' => 'Ngưỡng cảnh báo', 'route' => 'admin.thresholds.index', 'active' => 'admin.thresholds.*'],
    ],
    'NGUOI_SAN_XUAT' => [
        ['label' => 'Vùng trồng', 'route' => 'producer.planting-zones.index', 'active' => 'producer.planting-zones.*'],
        ['label' => 'Mùa vụ', 'route' => 'producer.seasons.index', 'active' => 'producer.seasons.*'],
        ['label' => 'Nhật ký canh tác', 'route' => 'producer.farming-logs.index', 'active' => 'producer.farming-logs.*'],
        ['label' => 'Lô thu hoạch', 'route' => 'producer.lots.index', 'active' => 'producer.lots.*'],
    ],
    'NV_SO_CHE_DONG_GOI' => [
        ['label' => 'Tiếp nhận nguyên liệu', 'route' => 'processing.inbound.index', 'active' => 'processing.inbound.*'],
        ['label' => 'Sơ chế & tạo lô', 'route' => 'processing.batches.index', 'active' => 'processing.batches.*'],
        ['label' => 'Đóng gói & mã kiện SSCC', 'route' => 'processing.packaging.index', 'active' => 'processing.packaging.*'],
    ],
    'QC' => [
        ['label' => 'Lô chờ kiểm định', 'route' => 'qc.pending.index', 'active' => 'qc.pending.*'],
        ['label' => 'Nhập kết quả kiểm định', 'route' => 'qc.inspections.create', 'active' => 'qc.inspections.create'],
        ['label' => 'Lịch sử kiểm định', 'route' => 'qc.inspections.index', 'active' => ['qc.inspections.index', 'qc.inspections.show']],
    ],
    'NV_KHO_LANH' => [
        ['label' => 'Nhập / xuất kho', 'route' => 'warehouse.operations.index', 'active' => 'warehouse.operations.*'],
        ['label' => 'Quản lý tồn kho', 'route' => 'warehouse.inventory.index', 'active' => 'warehouse.inventory.*'],
        ['label' => 'Giám sát nhiệt độ', 'route' => 'warehouse.monitoring.index', 'active' => 'warehouse.monitoring.*'],
    ],
    'TAI_XE' => [
        ['label' => 'Chuyến vận chuyển', 'route' => 'driver.trips.index', 'active' => 'driver.trips.*'],
        ['label' => 'Hành trình', 'route' => 'driver.journey.index', 'active' => 'driver.journey.*'],
        ['label' => 'Giao nhận', 'route' => 'driver.delivery.index', 'active' => 'driver.delivery.*'],
    ],
    'QUAN_LY_CHUOI' => [
        ['label' => 'Mùa vụ', 'route' => 'chain.seasons.index', 'active' => 'chain.seasons.*'],
        ['label' => 'Giám sát lô hàng', 'route' => 'chain.lots.index', 'active' => 'chain.lots.*'],
        ['label' => 'Kho lạnh & cảm biến', 'route' => 'chain.cold-storage.index', 'active' => 'chain.cold-storage.*'],
        ['label' => 'Giám sát vận chuyển', 'route' => 'chain.transport.index', 'active' => 'chain.transport.*'],
        ['label' => 'Phương tiện', 'route' => 'chain.vehicles.index', 'active' => 'chain.vehicles.*'],
        ['label' => 'Đối tác nhận hàng', 'route' => 'chain.partners.index', 'active' => 'chain.partners.*'],
        ['label' => 'Trung tâm cảnh báo', 'route' => 'chain.alerts.index', 'active' => 'chain.alerts.*'],
        ['label' => 'Báo cáo quản trị', 'route' => 'chain.reports.index', 'active' => 'chain.reports.*'],
    ],
];
