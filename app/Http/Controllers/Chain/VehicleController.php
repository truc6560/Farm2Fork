<?php

namespace App\Http\Controllers\Chain;

use App\Http\Controllers\Controller;
use App\Models\PhuongTien;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class VehicleController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim($request->query('q', ''));
        $status = $request->query('status', 'all');

        $query = PhuongTien::query();

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('bien_so', 'like', "%{$search}%")
                  ->orWhere('loai_xe', 'like', "%{$search}%")
                  ->orWhere('ghi_chu', 'like', "%{$search}%");
            });
        }

        if ($status !== 'all') {
            $query->where('trang_thai', $status);
        }

        $vehicles = $query->orderBy('bien_so')->paginate(15)->withQueryString();

        $stats = [
            'total'    => PhuongTien::count(),
            'san_sang' => PhuongTien::where('trang_thai', 'SAN_SANG')->count(),
            'hoat_dong' => PhuongTien::where('trang_thai', 'DANG_HOAT_DONG')->count(),
            'bao_duong' => PhuongTien::where('trang_thai', 'BAO_DUONG')->count(),
        ];

        return view('chain.vehicles.index', [
            'vehicles' => $vehicles,
            'search'   => $search,
            'status'   => $status,
            'stats'    => $stats,
            'trangThaiLabels' => PhuongTien::TRANG_THAI_LABELS,
        ]);
    }

    public function create(): View
    {
        return view('chain.vehicles.create', [
            'trangThaiLabels' => PhuongTien::TRANG_THAI_LABELS,
            'loaiXeOptions'   => $this->loaiXeOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->rules(), $this->messages());

        PhuongTien::create($validated);

        return redirect()->route('chain.vehicles.index')
            ->with('status', "Đã thêm phương tiện [{$validated['bien_so']}] thành công.");
    }

    public function edit(int $id): View
    {
        $vehicle = PhuongTien::findOrFail($id);

        return view('chain.vehicles.edit', [
            'vehicle'         => $vehicle,
            'trangThaiLabels' => PhuongTien::TRANG_THAI_LABELS,
            'loaiXeOptions'   => $this->loaiXeOptions(),
        ]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $vehicle = PhuongTien::findOrFail($id);

        $validated = $request->validate($this->rules($id), $this->messages());

        // Không cho phép ngưng hoạt động nếu đang có chuyến vận chuyển đang chạy
        if ($validated['trang_thai'] === 'NGUNG_HOAT_DONG' && $vehicle->trang_thai === 'DANG_HOAT_DONG') {
            return back()->withErrors([
                'trang_thai' => 'Không thể ngưng hoạt động phương tiện đang thực hiện chuyến vận chuyển.',
            ])->withInput();
        }

        $vehicle->update($validated);

        return redirect()->route('chain.vehicles.index')
            ->with('status', "Đã cập nhật phương tiện [{$vehicle->bien_so}] thành công.");
    }

    // ---------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------

    protected function rules(?int $ignoreId = null): array
    {
        return [
            'bien_so'               => [
                'required', 'string', 'max:20',
                Rule::unique('phuong_tien', 'bien_so')->ignore($ignoreId, 'phuong_tien_id'),
            ],
            'loai_xe'               => ['required', 'string', 'max:80'],
            'tai_trong'             => ['required', 'numeric', 'min:0', 'max:99999'],
            'don_vi_tai_trong'      => ['required', 'string', 'in:kg,tan'],
            'co_he_thong_lam_lanh'  => ['required', 'boolean'],
            'trang_thai'            => ['required', 'string', Rule::in(array_keys(PhuongTien::TRANG_THAI_LABELS))],
            'ghi_chu'               => ['nullable', 'string', 'max:500'],
        ];
    }

    protected function messages(): array
    {
        return [
            'bien_so.required'              => 'Vui lòng nhập biển số xe.',
            'bien_so.unique'                => 'Biển số xe này đã tồn tại trong hệ thống.',
            'loai_xe.required'              => 'Vui lòng chọn loại xe.',
            'tai_trong.required'            => 'Vui lòng nhập tải trọng.',
            'tai_trong.numeric'             => 'Tải trọng phải là số.',
            'don_vi_tai_trong.required'     => 'Vui lòng chọn đơn vị tải trọng.',
            'co_he_thong_lam_lanh.required' => 'Vui lòng xác nhận hệ thống làm lạnh.',
            'trang_thai.required'           => 'Vui lòng chọn trạng thái.',
        ];
    }

    protected function loaiXeOptions(): array
    {
        return [
            'XE_TAI_LANH'   => 'Xe tải lạnh',
            'XE_DONG_LANH'  => 'Xe đông lạnh',
            'XE_MAT'        => 'Xe mát (8–13°C)',
            'XE_TAI_THUONG' => 'Xe tải thường',
            'XE_BAN_TAI'    => 'Xe bán tải',
        ];
    }
}

