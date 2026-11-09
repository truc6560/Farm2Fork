<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\OrganizationRegistrationService;
use App\Support\RegistrationStatuses;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class RegistrationApprovalController extends Controller
{
    public function __construct(private OrganizationRegistrationService $service)
    {
    }

    public function index(Request $request): View
    {
        $registrations = $this->service->getAll($request->only(['status', 'q', 'type']));

        return view('admin.registrations.index', [
            'registrations' => $registrations,
            'statusOptions' => RegistrationStatuses::labels(),
            'filters' => $request->only(['status', 'q', 'type']),
        ]);
    }

    public function show(string $applicationCode): View
    {
        $registration = $this->service->findByCode($applicationCode);

        abort_unless($registration, 404);

        return view('admin.registrations.show', [
            'registration' => $registration,
            'statusOptions' => RegistrationStatuses::labels(),
        ]);
    }

    public function verifyContact(Request $request, string $applicationCode): RedirectResponse
    {
        $updated = $this->service->markContactVerified($applicationCode, (int) $request->user()->getAuthIdentifier());

        abort_unless($updated, 404);

        return redirect()
            ->route('admin.registrations.show', $applicationCode)
            ->with('status', 'Đã xác thực thông tin liên hệ cho hồ sơ.');
    }

    public function approve(Request $request, string $applicationCode): RedirectResponse
    {
        $request->validate([
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $note = $request->string('note')->toString();
        if ($note === '') {
            $note = 'Hồ sơ đạt yêu cầu và được phê duyệt.';
        }

        $updated = $this->service->updateStatus(
            $applicationCode,
            RegistrationStatuses::DA_PHE_DUYET,
            $note,
            (int) $request->user()->getAuthIdentifier()
        );

        abort_unless($updated, 404);

        return redirect()
            ->route('admin.registrations.show', $applicationCode)
            ->with('status', 'Đã phê duyệt hồ sơ. Có thể tiến hành cấp tài khoản.');
    }

    public function reject(Request $request, string $applicationCode): RedirectResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ], [
            'reason.required' => 'Vui lòng nhập lý do từ chối.',
        ]);

        $updated = $this->service->updateStatus(
            $applicationCode,
            RegistrationStatuses::TU_CHOI,
            $validated['reason'],
            (int) $request->user()->getAuthIdentifier()
        );

        abort_unless($updated, 404);

        return redirect()->route('admin.registrations.show', $applicationCode)
            ->with('status', 'Hồ sơ đã bị từ chối.');
    }

    public function requestSupplement(Request $request, string $applicationCode): RedirectResponse
    {
        $validated = $request->validate([
            'supplement_note' => ['required', 'string', 'max:500'],
        ], [
            'supplement_note.required' => 'Vui lòng nhập nội dung cần bổ sung.',
        ]);

        $updated = $this->service->updateStatus(
            $applicationCode,
            RegistrationStatuses::YEU_CAU_BO_SUNG,
            $validated['supplement_note'],
            (int) $request->user()->getAuthIdentifier()
        );

        abort_unless($updated, 404);

        return redirect()->route('admin.registrations.show', $applicationCode)
            ->with('status', 'Đã gửi yêu cầu bổ sung hồ sơ.');
    }

    public function downloadDocument(string $applicationCode, string $docId)
    {
        $path = $this->service->resolveDocumentPath($applicationCode, $docId);

        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->download($path);
    }
}
