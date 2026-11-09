<div class="f2f-mobile-zone-list">
    @forelse($mobileZones as $mobileZone)
        <a class="f2f-mobile-zone-row" href="{{ route('producer.planting-zones.show', $mobileZone->vung_trong_id) }}">
            <span class="f2f-mobile-zone-copy">
                <span class="f2f-mobile-zone-main">
                    <strong>{{ $mobileZone->ten_vung_trong }}</strong>
                    <code>{{ $mobileZone->ma_so_vung_trong ?: $mobileZone->ma_noi_bo }}</code>
                </span>
                <small>{{ $mobileZone->cay_trong_chinh ?: 'Chưa xác định cây trồng' }} · {{ $mobileZone->dien_tich_ha ? number_format($mobileZone->dien_tich_ha, 3) . ' ha' : 'Chưa nhập diện tích' }}</small>
            </span>
            <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m9 5 7 7-7 7"/></svg>
        </a>
    @empty
        <div class="f2f-mobile-zone-empty">Không có vùng trồng phù hợp. Hãy đổi bộ lọc hoặc khai báo vùng trồng mới.</div>
    @endforelse
</div>
