@php
    $rawIds = $traceLot->loai_lo === 'NGUYEN_LIEU' ? [$traceLot->lo_nong_san_id] : $traceLot->nguyenLieuDauVao->pluck('lo_nong_san_id')->all();
    $receipts = \Illuminate\Support\Facades\DB::table('tiep_nhan_nguyen_lieu as r')
        ->join('to_chuc as sender', 'sender.to_chuc_id', '=', 'r.to_chuc_giao_id')
        ->join('to_chuc as receiver', 'receiver.to_chuc_id', '=', 'r.to_chuc_nhan_id')
        ->whereIn('r.lo_nong_san_id', $rawIds)->select('r.*', 'sender.ten_to_chuc as ben_giao', 'receiver.ten_to_chuc as ben_nhan')->get();
    $outcomes = \Illuminate\Support\Facades\DB::table('so_luong_giao_nhan as q')
        ->join('chi_tiet_giao_nhan as c', 'c.chi_tiet_giao_nhan_id', '=', 'q.chi_tiet_giao_nhan_id')
        ->join('phieu_giao_hang as p', 'p.phieu_giao_hang_id', '=', 'c.phieu_giao_hang_id')
        ->join('to_chuc as o', 'o.to_chuc_id', '=', 'p.to_chuc_nhan_id')
        ->whereIn('q.lo_nong_san_id', $traceLot->loai_lo === 'NGUYEN_LIEU' ? $traceLot->thanhPhamDauRa->pluck('lo_nong_san_id')->push($traceLot->lo_nong_san_id)->all() : [$traceLot->lo_nong_san_id])
        ->select('q.*', 'p.ma_phieu_giao_hang', 'o.ten_to_chuc', 'c.trang_thai_giao_nhan', 'c.thoi_gian_giao_nhan')->get();
    $movements = \Illuminate\Support\Facades\DB::table('bien_dong_kho as m')
        ->join('kho_lanh as w', 'w.kho_lanh_id', '=', 'm.kho_lanh_id')
        ->where('m.lo_nong_san_id', $traceLot->lo_nong_san_id)
        ->orderBy('m.thoi_gian')->select('m.*', 'w.ten_kho')->get();
@endphp
<div class="f2f-card" style="margin-bottom:16px">
    <h3>Hồ sơ một bước trước – một bước sau</h3>
    @forelse($receipts as $receipt)
        <p>{{ $receipt->ben_giao }} → {{ $receipt->ben_nhan }} · {{ $receipt->thoi_gian_nhan }}:
            giao {{ $receipt->so_luong_giao }}, nhận {{ $receipt->so_luong_nhan }} {{ $receipt->don_vi_tinh }}. {{ $receipt->ghi_chu }}</p>
    @empty
        <p>Chưa có hồ sơ tiếp nhận nguyên liệu.</p>
    @endforelse
    @forelse($outcomes as $outcome)
        <p>{{ $outcome->ma_phieu_giao_hang }} → {{ $outcome->ten_to_chuc }}:
            giao {{ $outcome->so_luong_giao }} {{ $outcome->don_vi_tinh }},
            thực nhận {{ $outcome->so_luong_nhan ?? 'chưa xác nhận' }}.
            {{ $outcome->trang_thai_giao_nhan }} · {{ $outcome->thoi_gian_giao_nhan }} {{ $outcome->ly_do }}</p>
    @empty
        <p>Chưa có phiếu xuất hàng có số lượng đã chốt.</p>
    @endforelse
    @foreach($movements as $movement)
        <p>{{ $movement->thoi_gian }} · {{ $movement->loai }} · {{ $movement->ten_kho }}:
            {{ $movement->so_luong }} {{ $movement->don_vi_tinh }}
            @if($movement->phieu_giao_hang_id) · Phiếu giao #{{ $movement->phieu_giao_hang_id }} @endif
        </p>
    @endforeach
</div>
