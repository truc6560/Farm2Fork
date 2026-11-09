<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

class InitialPasswordStore
{
    private const FILE_PATH = 'auth_meta/initial_password_flags.json';

    public function shouldForceChange(int $accountId): bool
    {
        $flags = $this->load();

        return (bool) ($flags[$accountId] ?? false);
    }

    public function markForceChange(int $accountId): void
    {
        $flags = $this->load();
        $flags[$accountId] = true;
        $this->save($flags);
    }

    public function clearForceChange(int $accountId): void
    {
        $flags = $this->load();
        $flags[$accountId] = false;
        $this->save($flags);
    }

    private function load(): array
    {
        if (!Storage::disk('local')->exists(self::FILE_PATH)) {
            return [];
        }

        $raw = Storage::disk('local')->get(self::FILE_PATH);
        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function save(array $flags): void
    {
        Storage::disk('local')->put(self::FILE_PATH, json_encode($flags, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
}
