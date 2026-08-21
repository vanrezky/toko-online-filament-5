<li>
    <div class="flex items-center gap-2 py-1" style="padding-left: {{ $depth * 1.5 }}rem;">
        <span class="inline-block h-2 w-2 shrink-0 rounded-full {{ $node['span']->isFailed ? 'bg-danger-500' : ($node['span']->isSlow ? 'bg-warning-500' : 'bg-primary-500') }}"></span>
        <span class="min-w-0 max-w-72 truncate font-medium">{{ $node['span']->name }}</span>
        <span class="shrink-0 font-mono text-xs text-gray-500 dark:text-gray-400">{{ $node['span']->durationMs }}ms</span>
        @if ($node['span']->isSlow)
            <span class="rounded-md bg-warning-50 px-2 py-0.5 text-xs font-medium text-warning-700 dark:bg-warning-500/10 dark:text-warning-400">{{ __('admin/observability-trace-detail-page.badge.slow') }}</span>
        @endif
        @if ($node['span']->isFailed)
            <span class="rounded-md bg-danger-50 px-2 py-0.5 text-xs font-medium text-danger-700 dark:bg-danger-500/10 dark:text-danger-400">{{ __('admin/observability-traces-page.status.ERROR') }}</span>
        @endif
    </div>
    @if ($node['children'] !== [])
        <ul class="space-y-1">
            @foreach ($node['children'] as $child)
                @include('filament.pages.partials.trace-span-tree', ['node' => $child, 'depth' => $depth + 1])
            @endforeach
        </ul>
    @endif
</li>