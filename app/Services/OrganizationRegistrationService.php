<?php

namespace App\Services;

use App\Models\ToChuc;
use App\Support\RegistrationStatuses;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class OrganizationRegistrationService
{
    private const ROOT_PATH = 'organization_registrations';

    public function create(array $input): array
    {
        $maHoSo = $this->generateApplicationCode();

        $toChuc = ToChuc::create([
            'ma_to_chuc' => $this->generateOrganizationCode($input['organization_type']),
            'ten_to_chuc' => $input['organization_name'],
            'loai_to_chuc' => $input['organization_type'],
            'ma_so_thue' => $input['legal_id'],
            'dia_chi' => $input['organization_address'],
            'email' => $input['contact_email'],
            'so_dien_thoai' => $input['contact_phone'],
            'trang_thai' => RegistrationStatuses::CHO_XAC_THUC,
        ]);

        $filePayload = $this->storeFiles(
            $maHoSo,
            $input['legal_documents'] ?? [],
            $input['certification_documents'] ?? []
        );

        $this->saveMeta($maHoSo, [
            'application_code' => $maHoSo,
            'organization_id' => $toChuc->to_chuc_id,
            'status' => RegistrationStatuses::CHO_XAC_THUC,
            'organization' => [
                'type' => $input['organization_type'],
                'name' => $input['organization_name'],
                'representative' => $input['representative_name'],
                'legal_id' => $input['legal_id'],
                'contact_phone' => $input['contact_phone'],
                'contact_email' => $input['contact_email'],
                'address' => $input['organization_address'],
                'cultivation_region' => $input['cultivation_region'],
            ],
            'infrastructure' => [
                'cold_chain_items' => $input['cold_chain_items'] ?? [],
                'sensors' => $input['sensors'] ?? [],
            ],
            'documents' => $filePayload,
            'contact_verification' => [
                'mode' => config('app.env') === 'local' ? 'SIMULATED' : 'UNCONFIGURED',
                'verified' => false,
                'last_message' => config('app.env') === 'local'
                    ? 'Hệ thống đang ở chế độ mô phỏng xác thực liên hệ trong môi trường phát triển.'
                    : 'Chưa cấu hình kênh email/OTP thực tế.',
            ],
            'history' => [
                [
                    'status' => RegistrationStatuses::CHO_XAC_THUC,
                    'note' => 'Hồ sơ được tạo mới.',
                    'by' => 'PUBLIC_USER',
                    'at' => now()->toDateTimeString(),
                ],
            ],
            'approval' => [
                'approved_by_account_id' => null,
                'approved_at' => null,
                'reject_reason' => null,
                'supplement_request' => null,
            ],
            'account_provisioning' => [
                'provisioned' => false,
                'provisioned_account_id' => null,
                'provisioned_at' => null,
            ],
            'created_at' => now()->toDateTimeString(),
            'updated_at' => now()->toDateTimeString(),
        ]);

        return [
            'application_code' => $maHoSo,
            'organization_id' => $toChuc->to_chuc_id,
            'status' => RegistrationStatuses::CHO_XAC_THUC,
        ];
    }

    public function getAll(array $filters = []): array
    {
        $rows = [];

        foreach (Storage::disk('local')->files(self::ROOT_PATH) as $filePath) {
            if (!str_ends_with($filePath, '.json')) {
                continue;
            }

            $payload = $this->readMetaByPath($filePath);

            if (!$payload) {
                continue;
            }

            $rows[] = $payload;
        }

        usort($rows, fn ($a, $b) => strcmp($b['updated_at'] ?? '', $a['updated_at'] ?? ''));

        return array_values(array_filter($rows, function (array $item) use ($filters) {
            $status = $filters['status'] ?? null;
            $keyword = trim((string) ($filters['q'] ?? ''));
            $type = $filters['type'] ?? null;

            if ($status && $item['status'] !== $status) {
                return false;
            }

            if ($type && ($item['organization']['type'] ?? null) !== $type) {
                return false;
            }

            if ($keyword !== '') {
                $haystack = mb_strtolower(implode(' ', [
                    $item['application_code'] ?? '',
                    $item['organization']['name'] ?? '',
                    $item['organization']['representative'] ?? '',
                    $item['organization']['type'] ?? '',
                ]));

                if (!str_contains($haystack, mb_strtolower($keyword))) {
                    return false;
                }
            }

            return true;
        }));
    }

    public function findByCode(string $applicationCode): ?array
    {
        $path = $this->metaPath($applicationCode);

        if (!Storage::disk('local')->exists($path)) {
            return null;
        }

        return $this->readMetaByPath($path);
    }

    public function updateStatus(string $applicationCode, string $status, string $note, int $actorAccountId): ?array
    {
        $payload = $this->findByCode($applicationCode);

        if (!$payload) {
            return null;
        }

        $payload['status'] = $status;
        $payload['updated_at'] = now()->toDateTimeString();
        $payload['history'][] = [
            'status' => $status,
            'note' => $note,
            'by' => $actorAccountId,
            'at' => now()->toDateTimeString(),
        ];

        if ($status === RegistrationStatuses::DA_PHE_DUYET) {
            $payload['approval']['approved_by_account_id'] = $actorAccountId;
            $payload['approval']['approved_at'] = now()->toDateTimeString();
            $payload['approval']['reject_reason'] = null;
            $payload['approval']['supplement_request'] = null;
        }

        if ($status === RegistrationStatuses::TU_CHOI) {
            $payload['approval']['reject_reason'] = $note;
        }

        if ($status === RegistrationStatuses::YEU_CAU_BO_SUNG) {
            $payload['approval']['supplement_request'] = $note;
        }

        ToChuc::where('to_chuc_id', $payload['organization_id'])->update([
            'trang_thai' => $status,
        ]);

        $this->saveMeta($applicationCode, $payload);

        return $payload;
    }

    public function markContactVerified(string $applicationCode, int $actorAccountId): ?array
    {
        $payload = $this->findByCode($applicationCode);

        if (!$payload) {
            return null;
        }

        $payload['contact_verification']['verified'] = true;
        $payload['contact_verification']['verified_at'] = now()->toDateTimeString();
        $payload['contact_verification']['verified_by'] = $actorAccountId;
        $payload['contact_verification']['last_message'] = 'Thông tin liên hệ đã được xác thực bởi quản trị viên.';

        if ($payload['status'] === RegistrationStatuses::CHO_XAC_THUC) {
            $payload['status'] = RegistrationStatuses::CHO_DUYET;
            ToChuc::where('to_chuc_id', $payload['organization_id'])->update([
                'trang_thai' => RegistrationStatuses::CHO_DUYET,
            ]);
            $payload['history'][] = [
                'status' => RegistrationStatuses::CHO_DUYET,
                'note' => 'Đã xác thực thông tin liên hệ. Chuyển sang chờ duyệt hồ sơ.',
                'by' => $actorAccountId,
                'at' => now()->toDateTimeString(),
            ];
        }

        $payload['updated_at'] = now()->toDateTimeString();
        $this->saveMeta($applicationCode, $payload);

        return $payload;
    }

    public function markProvisioned(string $applicationCode, int $accountId): ?array
    {
        $payload = $this->findByCode($applicationCode);

        if (!$payload) {
            return null;
        }

        $payload['status'] = RegistrationStatuses::DA_CAP_TAI_KHOAN;
        $payload['account_provisioning'] = [
            'provisioned' => true,
            'provisioned_account_id' => $accountId,
            'provisioned_at' => now()->toDateTimeString(),
        ];
        $payload['updated_at'] = now()->toDateTimeString();
        $payload['history'][] = [
            'status' => RegistrationStatuses::DA_CAP_TAI_KHOAN,
            'note' => 'Tài khoản đã được cấp sau phê duyệt.',
            'by' => $accountId,
            'at' => now()->toDateTimeString(),
        ];

        ToChuc::where('to_chuc_id', $payload['organization_id'])->update([
            'trang_thai' => RegistrationStatuses::DA_CAP_TAI_KHOAN,
        ]);

        $this->saveMeta($applicationCode, $payload);

        return $payload;
    }

    public function resolveDocumentPath(string $applicationCode, string $key): ?string
    {
        $payload = $this->findByCode($applicationCode);

        if (!$payload) {
            return null;
        }

        foreach (['legal_documents', 'certification_documents'] as $group) {
            foreach (($payload['documents'][$group] ?? []) as $doc) {
                if (($doc['id'] ?? null) === $key) {
                    return $doc['path'] ?? null;
                }
            }
        }

        return null;
    }

    private function storeFiles(string $applicationCode, array $legalFiles, array $certificateFiles): array
    {
        return [
            'legal_documents' => $this->storeFileSet($applicationCode, 'legal_documents', $legalFiles),
            'certification_documents' => $this->storeFileSet($applicationCode, 'certification_documents', $certificateFiles),
        ];
    }

    private function storeFileSet(string $applicationCode, string $group, array $files): array
    {
        $result = [];

        foreach ($files as $file) {
            if (!$file instanceof UploadedFile) {
                continue;
            }

            $path = $file->storeAs(
                self::ROOT_PATH.'/'.$applicationCode.'/'.$group,
                now()->format('YmdHis').'_'.Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)).'.'.$file->getClientOriginalExtension(),
                'local'
            );

            $result[] = [
                'id' => (string) Str::uuid(),
                'name' => $file->getClientOriginalName(),
                'mime' => $file->getClientMimeType(),
                'size' => $file->getSize(),
                'path' => $path,
                'uploaded_at' => now()->toDateTimeString(),
            ];
        }

        return $result;
    }

    private function readMetaByPath(string $path): ?array
    {
        $raw = Storage::disk('local')->get($path);
        $data = json_decode($raw, true);

        return is_array($data) ? $data : null;
    }

    private function saveMeta(string $applicationCode, array $payload): void
    {
        Storage::disk('local')->put(
            $this->metaPath($applicationCode),
            json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );
    }

    private function metaPath(string $applicationCode): string
    {
        return self::ROOT_PATH.'/'.$applicationCode.'.json';
    }

    private function generateApplicationCode(): string
    {
        return 'HS-'.now()->format('Ymd').'-'.strtoupper(Str::random(6));
    }

    private function generateOrganizationCode(string $organizationType): string
    {
        $prefix = match ($organizationType) {
            'NONG_HO' => 'NCC-NH-',
            'HTX' => 'NCC-HTX-',
            'DOANH_NGHIEP_NONG_NGHIEP' => 'NCC-DN-',
            default => 'NCC-ORG-',
        };

        return $prefix.strtoupper(Str::random(6));
    }
}
