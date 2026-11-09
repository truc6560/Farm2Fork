<?php

namespace App\Http\Controllers\Chain;

use App\Http\Controllers\Controller;
use App\Models\ToChuc;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PartnerController extends Controller
{
    /** Loại tổ chức được quản lý ở đây (đối tác nhận hàng). */
    private const LOAI_TO_CHUC = 'KHACH_HANG';

    public function index(Request $request): View
    {
        $search = trim($request->query('q', ''));
        $status = $request->query('status', 'all');

        $query = ToChuc::where('loai_to_chuc', self::LOAI_TO_CHUC);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('ten_to_chuc', 'like', "%{$search}%")
                  ->orWhere('ma_to_chuc', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('so_dien_thoai', 'like', "%{$search}%");
            });
        }

        if ($status !== 'all') {
            $query->where('trang_thai', $status);
        }

        $partners = $query->orderBy('ten_to_chuc')->paginate(15)->withQueryString();

        // Bổ sung đếm phiếu giao hàng cho mỗi đối tác
        $partners->getCollection()->each(function ($p) {
            $p->tong_phieu_giao = $p->phieuGiaoHang()->count();
        });

        $stats = [
            'total'     => ToChuc::where('loai_to_chuc', self::LOAI_TO_CHUC)->count(),
            'hoat_dong' => ToChuc::where('loai_to_chuc', self::LOAI_TO_CHUC)->where('trang_thai', 'HOAT_DONG')->count(),
            'tam_ngung' => ToChuc::where('loai_to_chuc', self::LOAI_TO_CHUC)->where('trang_thai', 'TAM_NGUNG')->count(),
        ];

        return view('chain.partners.index', [
            'partners' => $partners,
            'search'   => $search,
            'status'   => $status,
            'stats'    => $stats,
        ]);
    }

    public function create(): View
    {
        return view('chain.partners.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->rules(), $this->messages());

        // Tự động điền loại tổ chức
        $validated['loai_to_chuc'] = self::LOAI_TO_CHUC;

        // Tạo mã tổ chức nếu chưa có
        if (empty($validated['ma_to_chuc'])) {
            $validated['ma_to_chuc'] = $this->generateMaToChuc();
        }

        $partner = ToChuc::create($validated);

        return redirect()->route('chain.partners.index')
            ->with('status', "Đã thêm đối tác [{$partner->ten_to_chuc}] thành công.");
    }

    public function edit(int $id): View
    {
        $partner = ToChuc::where('loai_to_chuc', self::LOAI_TO_CHUC)->findOrFail($id);

        return view('chain.partners.edit', [
            'partner' => $partner,
        ]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $partner = ToChuc::where('loai_to_chuc', self::LOAI_TO_CHUC)->findOrFail($id);

        $validated = $request->validate($this->rules($id), $this->messages());

        // Không cho phép ngưng đối tác đang có phiếu giao hàng chờ xử lý
        if ($validated['trang_thai'] === 'NGUNG_HOAT_DONG') {
            $pendingCount = $partner->phieuGiaoHang()
                ->whereNotIn('trang_thai', ['DA_GIAO', 'HUY', 'TRA_VE'])
                ->count();
            if ($pendingCount > 0) {
                return back()->withErrors([
                    'trang_thai' => "Không thể ngưng đối tác đang có {$pendingCount} phiếu giao hàng chưa hoàn tất.",
                ])->withInput();
            }
        }

        $partner->update($validated);

        return redirect()->route('chain.partners.index')
            ->with('status', "Đã cập nhật thông tin đối tác [{$partner->ten_to_chuc}] thành công.");
    }

    // ---------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------

    protected function rules(?int $ignoreId = null): array
    {
        return [
            'ten_to_chuc'   => ['required', 'string', 'max:255'],
            'ma_to_chuc'    => [
                'nullable', 'string', 'max:50',
                Rule::unique('to_chuc', 'ma_to_chuc')->ignore($ignoreId, 'to_chuc_id'),
            ],
            'ma_so_thue'    => ['nullable', 'string', 'max:20'],
            'dia_chi'       => ['nullable', 'string', 'max:500'],
            'email'         => ['nullable', 'email', 'max:255'],
            'so_dien_thoai' => ['nullable', 'string', 'max:20'],
            'trang_thai'    => ['required', 'string', 'in:HOAT_DONG,TAM_NGUNG,NGUNG_HOAT_DONG'],
        ];
    }

    protected function messages(): array
    {
        return [
            'ten_to_chuc.required' => 'Vui lòng nhập tên đối tác.',
            'ma_to_chuc.unique'    => 'Mã tổ chức này đã tồn tại.',
            'email.email'          => 'Địa chỉ email không hợp lệ.',
            'trang_thai.required'  => 'Vui lòng chọn trạng thái.',
        ];
    }

    protected function generateMaToChuc(): string
    {
        $prefix = 'KH-' . date('y');
        $last = ToChuc::where('ma_to_chuc', 'like', $prefix . '%')
            ->orderByDesc('ma_to_chuc')
            ->value('ma_to_chuc');

        $seq = $last ? ((int)substr($last, -4)) + 1 : 1;

        return $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }
}

