<?php

namespace App\Services;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class R2StorageTester
{
    public function __construct(private readonly ?Filesystem $filesystem = null) {}

    /**
     * @return array{success: bool, operation: string, key: string}
     */
    public function test(): array
    {
        $key = 'r2-tests/'.Str::uuid().'.txt';
        $payload = 'toko-online-r2-test';
        $disk = null;
        $created = false;
        $operation = 'connect';
        $success = false;

        try {
            $disk = $this->filesystem ?? Storage::disk('r2');
            $operation = 'write';

            if (! $disk->put($key, $payload)) {
                return ['success' => false, 'operation' => 'write', 'key' => $key];
            }

            $created = true;
            $operation = 'read';

            if ($disk->get($key) !== $payload) {
                return ['success' => false, 'operation' => 'read', 'key' => $key];
            }

            $success = true;
        } catch (Throwable) {
            return ['success' => false, 'operation' => $operation, 'key' => $key];
        } finally {
            if ($created && $disk) {
                $operation = 'delete';

                try {
                    if (! $disk->delete($key)) {
                        $success = false;
                    }
                } catch (Throwable) {
                    $success = false;
                }
            }
        }

        return ['success' => $success, 'operation' => $success ? 'complete' : $operation, 'key' => $key];
    }
}
