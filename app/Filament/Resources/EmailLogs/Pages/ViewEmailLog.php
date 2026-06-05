<?php

namespace App\Filament\Resources\EmailLogs\Pages;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use App\Filament\Resources\EmailLogs\EmailLogResource;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\HtmlString;

class ViewEmailLog extends ViewRecord
{
    protected static string $resource = EmailLogResource::class;

    public function getTitle(): string
    {
        return __('admin/email-log-resource.pages.view.title');
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make(__('admin/email-log-resource.sections.email_information'))
                    ->schema([
                        TextEntry::make('template_code')
                            ->label(__('admin/email-log-resource.entries.template_code'))
                            ->badge()
                            ->color('primary'),
                        TextEntry::make('recipient_email')
                            ->label(__('admin/email-log-resource.entries.recipient_email')),
                        TextEntry::make('subject')
                            ->label(__('admin/email-log-resource.entries.subject')),
                        TextEntry::make('status')
                            ->label(__('admin/email-log-resource.entries.status'))
                            ->badge()
                            ->color(fn ($state) => match ($state) {
                                'sent' => 'success',
                                'failed' => 'danger',
                                'pending' => 'warning',
                            })
                            ->formatStateUsing(fn ($state) => __('admin/email-log-resource.status_options.' . $state)),
                    ])->columns(2),
                Section::make(__('admin/email-log-resource.sections.content'))
                    ->schema([
                        TextEntry::make('body')
                            ->label(__('admin/email-log-resource.entries.body'))
                            ->formatStateUsing(fn ($state) => new HtmlString(
                                '<iframe class="w-full min-w-[400px] min-h-[400px] rounded-lg border border-gray-200 bg-white" srcdoc="'
                                . e((string) $state)
                                . '"></iframe>'
                            ))
                            ->html(),
                    ]),
                Section::make(__('admin/email-log-resource.sections.placeholders'))
                    ->schema([
                        TextEntry::make('placeholders')
                            ->label(__('admin/email-log-resource.entries.placeholders'))
                            ->formatStateUsing(fn ($state) => new HtmlString(
                                '<pre class="whitespace-pre-wrap break-words overflow-x-auto rounded-lg bg-gray-50 p-4 text-xs">'
                                . e(json_encode($state ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}')
                                . '</pre>'
                            ))
                            ->html(),
                    ]),
                Section::make(__('admin/email-log-resource.sections.metadata'))
                    ->schema([
                        TextEntry::make('error_message')
                            ->label(__('admin/email-log-resource.entries.error_message'))
                            ->wrap(),
                        TextEntry::make('sent_at')
                            ->label(__('admin/email-log-resource.entries.sent_at'))
                            ->dateTime(),
                        TextEntry::make('created_at')
                            ->label(__('admin/email-log-resource.entries.created_at'))
                            ->dateTime(),
                    ])->columns(2),
            ])
            ->columns(1);
    }
}
