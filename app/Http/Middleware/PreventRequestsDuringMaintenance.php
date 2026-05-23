<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use App\Settings\GeneralSettings;
use Closure;
use Illuminate\Foundation\Http\Middleware\PreventRequestsDuringMaintenance as Middleware;
use Symfony\Component\HttpKernel\Exception\HttpException;

class PreventRequestsDuringMaintenance extends Middleware
{
    /**
     * The URIs that should be reachable while maintenance mode is enabled.
     *
     * @var array<int, string>
     */
    protected $except = [
        'admin*',
        'livewire*',
    ];

    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     *
     * @throws HttpException
     */
    public function handle($request, Closure $next)
    {
        $settings = app(GeneralSettings::class);

        if (!$settings->site_active && !$this->inExceptArray($request)) {
            throw new HttpException(503);
        }

        return parent::handle($request, $next);
    }
}
