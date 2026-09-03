<?php

namespace Tests\Unit\Services;

use App\Services\R2StorageTester;
use Illuminate\Contracts\Filesystem\Filesystem;
use Mockery;
use PHPUnit\Framework\TestCase;

class R2StorageTesterTest extends TestCase
{
    public function test_it_writes_reads_verifies_and_deletes_a_temporary_object(): void
    {
        $disk = Mockery::mock(Filesystem::class);
        $disk->shouldReceive('put')->once()->withArgs(function (string $key, string $payload): bool {
            return str_starts_with($key, 'r2-tests/') && $payload === 'toko-online-r2-test';
        })->andReturn(true);
        $disk->shouldReceive('get')->once()->withArgs(function (string $key): bool {
            return str_starts_with($key, 'r2-tests/');
        })->andReturn('toko-online-r2-test');
        $disk->shouldReceive('delete')->once()->withArgs(function (string $key): bool {
            return str_starts_with($key, 'r2-tests/');
        })->andReturn(true);

        $result = (new R2StorageTester($disk))->test();

        $this->assertTrue($result['success']);
        $this->assertSame('complete', $result['operation']);
    }

    public function test_it_returns_a_safe_failure_when_read_content_does_not_match(): void
    {
        $disk = Mockery::mock(Filesystem::class);
        $disk->shouldReceive('put')->once()->andReturn(true);
        $disk->shouldReceive('get')->once()->andReturn('unexpected-content');
        $disk->shouldReceive('delete')->once()->andReturn(true);
        $result = (new R2StorageTester($disk))->test();

        $this->assertFalse($result['success']);
        $this->assertSame('read', $result['operation']);
        $this->assertStringStartsWith('r2-tests/', $result['key']);
    }

    public function test_it_attempts_cleanup_when_read_throws(): void
    {
        $disk = Mockery::mock(Filesystem::class);
        $disk->shouldReceive('put')->once()->andReturn(true);
        $disk->shouldReceive('get')->once()->andThrow(new \RuntimeException('secret=must-not-leak'));
        $disk->shouldReceive('delete')->once()->andReturn(true);
        $result = (new R2StorageTester($disk))->test();

        $this->assertFalse($result['success']);
        $this->assertSame('read', $result['operation']);
        $this->assertStringNotContainsString('secret', json_encode($result));
    }

    public function test_it_reports_delete_failure_as_a_failed_test(): void
    {
        $disk = Mockery::mock(Filesystem::class);
        $disk->shouldReceive('put')->once()->andReturn(true);
        $disk->shouldReceive('get')->once()->andReturn('toko-online-r2-test');
        $disk->shouldReceive('delete')->once()->andReturn(false);
        $result = (new R2StorageTester($disk))->test();

        $this->assertFalse($result['success']);
        $this->assertSame('delete', $result['operation']);
    }

    public function test_it_generates_unique_keys_for_each_test_run(): void
    {
        $keys = [];
        $disk = Mockery::mock(Filesystem::class);
        $disk->shouldReceive('put')->twice()->withArgs(function (string $key, string $payload) use (&$keys): bool {
            $keys[] = $key;

            return $payload === 'toko-online-r2-test';
        })->andReturn(true);
        $disk->shouldReceive('get')->twice()->andReturn('toko-online-r2-test');
        $disk->shouldReceive('delete')->twice()->andReturn(true);

        $tester = new R2StorageTester($disk);
        $tester->test();
        $tester->test();

        $this->assertCount(2, array_unique($keys));
    }
}
