<?php

namespace Tests\Unit\Settings;

use App\Settings\GeneralSettings;
use ReflectionClass;
use RuntimeException;
use Tests\TestCase;

class GeneralSettingsTest extends TestCase
{
    public function test_mail_settings_are_restored_after_a_temporary_override_fails(): void
    {
        $original = [
            'mail.mailers.smtp.host' => 'original.test',
            'mail.mailers.smtp.port' => 2525,
            'mail.mailers.smtp.encryption' => 'tls',
            'mail.mailers.smtp.username' => 'original-user',
            'mail.mailers.smtp.password' => 'original-password',
            'mail.from.address' => 'original@example.test',
            'mail.from.name' => 'Original',
        ];

        config($original);

        $settings = (new ReflectionClass(GeneralSettings::class))->newInstanceWithoutConstructor();

        try {
            $settings->withMailSettings([
                'mail_host' => 'temporary.test',
                'mail_port' => '465',
                'encryption' => 'ssl',
                'username' => 'temporary-user',
                'password' => 'temporary-password',
                'from_address' => 'temporary@example.test',
                'from_name' => 'Temporary',
            ], function (): void {
                $this->assertSame('temporary.test', config('mail.mailers.smtp.host'));
                $this->assertSame('temporary@example.test', config('mail.from.address'));

                throw new RuntimeException('expected temporary failure');
            });
            $this->fail('Expected the temporary mail operation to fail.');
        } catch (RuntimeException $exception) {
            $this->assertSame('expected temporary failure', $exception->getMessage());
        }

        foreach ($original as $key => $value) {
            $this->assertSame($value, config($key));
        }
    }
}
