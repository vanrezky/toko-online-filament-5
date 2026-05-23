<?php

namespace App\Filament\Resources\EmailLogs\Pages;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use App\Filament\Resources\EmailLogs\EmailLogResource;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\HtmlString;

class ViewEmailLog extends ViewRecord
{
    protected static string $resource = EmailLogResource::class;

    public function infolist(Schema $schema): Schema
    {
        return $infolist
            ->schema([
                Section::make('Email Information')
                    ->schema([
                        TextEntry::make('template_code')
                            ->badge()
                            ->color('primary'),
                        TextEntry::make('recipient_email'),
                        TextEntry::make('subject'),
                        TextEntry::make('status')
                            ->badge()
                            ->color(fn ($state) => match ($state) {
                                'sent' => 'success',
                                'failed' => 'danger',
                                'pending' => 'warning',
                            }),
                    ])->columns(2),
                Section::make('Content')
                    ->schema([
                        TextEntry::make('body')
                            ->label('Body Content')
                            ->html()
                            ->formatStateUsing(fn ($state) => new HtmlString($state)),
                    ]),
                Section::make('Placeholders Used')
                    ->schema([
                        KeyValueEntry::make('placeholders'),
                    ]),
                Section::make('Metadata')
                    ->schema([
                        TextEntry::make('error_message'),
                        TextEntry::make('sent_at')
                            ->dateTime(),
                        TextEntry::make('created_at')
                            ->dateTime(),
                    ])->columns(2),
            ]);
    }
}
