<?php

namespace Tests\Unit;

use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PersistentUploadStorageTest extends TestCase
{
    public function test_persistent_upload_disk_is_environment_configured_and_defaults_to_r2(): void
    {
        config(['filesystems.upload_disk' => 'r2']);

        $this->assertSame('r2', config('filesystems.upload_disk'));
        $this->assertSame('s3', config('filesystems.disks.r2.driver'));
        $this->assertTrue(config('filesystems.disks.r2.use_path_style_endpoint'));
    }

    public function test_temporary_imports_can_continue_using_local_storage(): void
    {
        Storage::fake('local');
        Storage::fake('r2');

        Storage::disk('local')->put('temporary/product-import.xlsx', 'temporary');

        Storage::disk('local')->assertExists('temporary/product-import.xlsx');
        Storage::disk('r2')->assertMissing('temporary/product-import.xlsx');
    }
}
