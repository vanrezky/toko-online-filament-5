<?php

namespace App\Filament\Pages;

use App\Filament\Clusters\ObservabilityCluster;
use App\Filament\Resources\AuditLogs\AuditLogResource;
use App\Filament\Resources\IntegrationLogs\IntegrationLogResource;
use App\Modules\Platform\Tracing\Services\TraceService;
use App\Modules\Platform\Tracing\ValueObjects\TraceDetail;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Facades\Filament;
use Filament\Pages\Page;

class ObservabilityTraceDetail extends Page
{
    use HasPageShield;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-bars-3';

    protected static ?string $slug = 'traces/{trace}';

    protected static ?string $cluster = ObservabilityCluster::class;

    protected string $view = 'filament.pages.observability-trace-detail';

    public ?string $trace = null;

    public function getTitle(): string
    {
        return __('admin/observability-trace-detail-page.title');
    }

    public static function getNavigationLabel(): string
    {
        return __('admin/observability-trace-detail-page.navigation_label');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    /** @return array{detail: ?TraceDetail, integration_logs_url: ?string, audit_logs_url: ?string} */
    public function getViewData(): array
    {
        $detail = $this->trace !== null
            ? app(TraceService::class)->resolve($this->trace)
            : null;

        $integrationLogsUrl = null;
        $auditLogsUrl = null;

        if ($detail?->correlationId) {
            $integrationLogsUrl = IntegrationLogResource::getUrl('index')
                .'?filters[search][value]='.urlencode($detail->correlationId);
            $auditLogsUrl = AuditLogResource::getUrl('index')
                .'?filters[search][value]='.urlencode($detail->correlationId);
        }

        return [
            'detail' => $detail,
            'integration_logs_url' => $integrationLogsUrl,
            'audit_logs_url' => $auditLogsUrl,
        ];
    }

    public static function canAccess(): bool
    {
        $user = Filament::auth()?->user();

        return (bool) $user?->is_super_user || (bool) $user?->can('View:Traces');
    }
}
