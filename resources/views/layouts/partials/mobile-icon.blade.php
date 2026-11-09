<svg viewBox="0 0 24 24" width="23" height="23" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
    @switch($icon)
        @case('home')<path d="m3 10 9-7 9 7v10a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1z"/><path d="M9 21v-7h6v7"/>@break
        @case('field')<path d="M3 20c3-5 6-5 9 0 3-5 6-5 9 0"/><path d="M3 15c3-5 6-5 9 0 3-5 6-5 9 0"/><path d="M12 3v9"/><path d="M12 7c-2-2-4-2-5-1 0 2 2 4 5 4M12 7c2-2 4-2 5-1 0 2-2 4-5 4"/>@break
        @case('season')<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M7 3v4m10-4v4M3 10h18m-12 5 2 2 4-4"/>@break
        @case('inbox')<rect x="3" y="4" width="18" height="16" rx="2"/><path d="M3 14h5l2 3h4l2-3h5"/>@break
        @case('package')<path d="m12 2 9 5-9 5-9-5 9-5Zm-9 5v10l9 5 9-5V7M12 12v10"/>@break
        @case('truck')<path d="M3 5h11v12H3zM14 9h4l3 3v5h-7z"/><circle cx="7" cy="18" r="2"/><circle cx="18" cy="18" r="2"/>@break
        @case('list')<path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/>@break
        @case('journal')<path d="M5 3h13a2 2 0 0 1 2 2v15H7a3 3 0 0 0-3 1V5a2 2 0 0 1 1-2Z"/><path d="M4 19a3 3 0 0 1 3-2h13M8 8h8M8 12h6"/>@break
        @case('search')<circle cx="10.8" cy="10.8" r="6.8"/><path d="m16 16 5 5"/>@break
        @case('people')<circle cx="9" cy="8" r="3"/><path d="M3 20v-2a6 6 0 0 1 12 0v2M16 5a3 3 0 0 1 0 6m2 3a5 5 0 0 1 3 5v1"/>@break
        @case('users')<circle cx="12" cy="8" r="3"/><path d="M5 21v-2a7 7 0 0 1 14 0v2M4 5h2m-1-1v2m13-1h2m-1-1v2"/>@break
        @case('scan')<path d="M8 3H5a2 2 0 0 0-2 2v3m13-5h3a2 2 0 0 1 2 2v3M3 16v3a2 2 0 0 0 2 2h3m13-5v3a2 2 0 0 1-2 2h-3M7 12h10"/>@break
        @case('alert')<path d="M12 3 2 21h20L12 3Z"/><path d="M12 9v5m0 3h.01"/>@break
        @case('add')<path d="M12 4v16M4 12h16"/>@break
        @case('menu')<path d="M4 6h16M4 12h16M4 18h16"/>@break
    @endswitch
</svg>
