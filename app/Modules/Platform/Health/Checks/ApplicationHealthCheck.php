<?php

namespace App\Modules\Platform\Health\Checks;

use Spatie\Health\Checks\Check;
use Spatie\Health\Checks\Result;

final class ApplicationHealthCheck extends Check
{
    public function run(): Result
    {
        return Result::make()
            ->meta([
                'environment' => app()->environment(),
                'laravel_version' => app()->version(),
                'php_version' => PHP_VERSION,
            ])
            ->shortSummary('Application is running')
            ->ok();
    }
}
