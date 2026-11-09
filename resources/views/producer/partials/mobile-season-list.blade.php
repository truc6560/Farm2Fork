<div class="f2f-mobile-season-list">
    @forelse($mobileSeasons as $mobileSeason)
        <a class="f2f-mobile-season-row" href="{{ route('producer.seasons.show', $mobileSeason->mua_vu_id) }}">
            <span class="f2f-mobile-season-copy">
                <span class="f2f-mobile-season-main"><strong>{{ $mobileSeason->ten_mua_vu }}</strong><code>{{ $mobileSeason->ma_mua_vu }}</code></span>
                <small>{{ $mobileSeason->ngay_gieo?->format('d/m/Y') ?? 'Chưa có ngày gieo' }} → {{ $mobileSeason->ngay_thu_hoach_du_kien?->format('d/m/Y') ?? 'Chưa có ngày thu dự kiến' }}</small>
            </span>
            <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m9 5 7 7-7 7"/></svg>
        </a>
    @empty
        <div class="f2f-mobile-season-empty">Không có mùa vụ phù hợp. Hãy đổi bộ lọc hoặc tạo mùa vụ mới.</div>
    @endforelse
</div>
