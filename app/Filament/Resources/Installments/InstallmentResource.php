<?php

namespace App\Filament\Resources\Installments;

use App\Filament\Clusters\CustomerCluster;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Resources\Installments\Pages\ListInstallments;
use App\Filament\Resources\Installments\Pages\ViewInstallment;
use App\Filament\Resources\Installments\RelationManagers\PaymentsRelationManager;
use App\Models\Installment;
use Filament\Forms;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class InstallmentResource extends Resource
{
    protected static ?string $model = Installment::class;

    protected static ?string $cluster = CustomerCluster::class;
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-credit-card';
    protected static ?string $slug = 'installments';
    protected static ?int $navigationSort = 3;

    public static function getNavigationLabel(): string
    {
        return __('admin/installment-resource.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('admin/installment-resource.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin/installment-resource.plural_model_label');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('admin/installment-resource.sections.installment_info'))
                    ->schema([
                        TextInput::make('uuid')
                            ->disabled(),
                        Select::make('customer_id')
                            ->relationship('customer', 'first_name')
                            ->disabled(),
                        Select::make('installment_plan_id')
                            ->relationship('installmentPlan', 'tenor')
                            ->disabled(),
                        TextInput::make('principal_amount')
                            ->label(__('admin/installment-resource.fields.principal_amount'))
                            ->disabled(),
                        TextInput::make('fee_amount')
                            ->label(__('admin/installment-resource.fields.fee_amount'))
                            ->disabled(),
                        TextInput::make('total_amount')
                            ->label(__('admin/installment-resource.fields.total_amount'))
                            ->disabled(),
                        TextInput::make('monthly_amount')
                            ->label(__('admin/installment-resource.fields.monthly_amount'))
                            ->disabled(),
                    ])->columns(2),
                Section::make(__('admin/installment-resource.sections.payment_progress'))
                    ->schema([
                        TextInput::make('tenor')
                            ->label(__('admin/installment-resource.fields.tenor'))
                            ->disabled(),
                        TextInput::make('paid_installments')
                            ->label(__('admin/installment-resource.fields.paid_installments'))
                            ->disabled(),
                        TextInput::make('paid_amount')
                            ->label(__('admin/installment-resource.fields.paid_amount'))
                            ->disabled(),
                        TextInput::make('remaining_amount')
                            ->label(__('admin/installment-resource.fields.remaining_amount'))
                            ->disabled(),
                        Select::make('status')
                            ->options([
                                'active' => __('admin/installment-resource.status_options.active'),
                                'completed' => __('admin/installment-resource.status_options.completed'),
                                'overdue' => __('admin/installment-resource.status_options.overdue'),
                                'defaulted' => __('admin/installment-resource.status_options.defaulted'),
                            ])
                            ->disabled(),
                        TextInput::make('start_date')
                            ->disabled(),
                        TextInput::make('expected_end_date')
                            ->label(__('admin/installment-resource.fields.expected_end_date'))
                            ->disabled(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('uuid')
                    ->label(__('admin/installment-resource.columns.uuid'))
                    ->searchable()
                    ->formatStateUsing(fn (?string $state): ?string => $state ? Str::before($state, '-') : null)
                    ->copyable(),
                TextColumn::make('customer.full_name')
                    ->label(__('admin/installment-resource.columns.customer_full_name'))
                    ->searchable(),
                TextColumn::make('customer.customerLevel.name')
                    ->label(__('admin/installment-resource.columns.customer_level'))
                    ->badge(),
                TextColumn::make('total_amount')
                    ->label(__('admin/installment-resource.columns.total_amount'))
                    ->money('IDR')
                    ->sortable(),
                TextColumn::make('monthly_amount')
                    ->label(__('admin/installment-resource.columns.monthly_amount'))
                    ->money('IDR')
                    ->sortable(),
                TextColumn::make('tenor')
                    ->label(__('admin/installment-resource.columns.tenor'))
                    ->sortable(),
                TextColumn::make('paid_installments')
                    ->label(__('admin/installment-resource.columns.paid_installments'))
                    ->sortable(),
                BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'active',
                        'success' => 'completed',
                        'danger' => 'overdue',
                        'gray' => 'defaulted',
                    ]),
                TextColumn::make('start_date')
                    ->date()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'active' => __('admin/installment-resource.status_options.active'),
                        'completed' => __('admin/installment-resource.status_options.completed'),
                        'overdue' => __('admin/installment-resource.status_options.overdue'),
                        'defaulted' => __('admin/installment-resource.status_options.defaulted'),
                    ]),
                SelectFilter::make('customer_level')
                    ->relationship('customer.customerLevel', 'name')
                    ->label(__('admin/installment-resource.filters.customer_level')),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('admin/installment-resource.sections.installment_info'))
                    ->schema([
                        TextEntry::make('uuid')
                            ->label(__('admin/installment-resource.fields.uuid'))
                            ->formatStateUsing(fn (?string $state): ?string => $state ? Str::before($state, '-') : null),
                        TextEntry::make('customer.full_name')
                            ->label(__('admin/installment-resource.fields.customer_id')),
                        TextEntry::make('installmentPlan.tenor')
                            ->label(__('admin/installment-resource.fields.installment_plan_id'))
                            ->suffix(' Bulan'),
                        TextEntry::make('principal_amount')
                            ->label(__('admin/installment-resource.fields.principal_amount'))
                            ->money('IDR'),
                        TextEntry::make('fee_amount')
                            ->label(__('admin/installment-resource.fields.fee_amount'))
                            ->money('IDR'),
                        TextEntry::make('total_amount')
                            ->label(__('admin/installment-resource.fields.total_amount'))
                            ->money('IDR'),
                        TextEntry::make('monthly_amount')
                            ->label(__('admin/installment-resource.fields.monthly_amount'))
                            ->money('IDR'),
                    ])->columns(2),
                Section::make(__('admin/installment-resource.sections.payment_progress'))
                    ->schema([
                        TextEntry::make('tenor')
                            ->label(__('admin/installment-resource.fields.tenor')),
                        TextEntry::make('paid_installments')
                            ->label(__('admin/installment-resource.fields.paid_installments')),
                        TextEntry::make('paid_amount')
                            ->label(__('admin/installment-resource.fields.paid_amount'))
                            ->money('IDR'),
                        TextEntry::make('remaining_amount')
                            ->label(__('admin/installment-resource.fields.remaining_amount'))
                            ->money('IDR'),
                        TextEntry::make('status')
                            ->label(__('admin/installment-resource.fields.status'))
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'completed' => 'success',
                                'overdue' => 'danger',
                                'defaulted' => 'gray',
                                default => 'warning',
                            })
                            ->formatStateUsing(fn (string $state): string => __('admin/installment-resource.status_options.' . $state)),
                        TextEntry::make('start_date')
                            ->label(__('admin/installment-resource.fields.start_date'))
                            ->date(),
                        TextEntry::make('expected_end_date')
                            ->label(__('admin/installment-resource.fields.expected_end_date'))
                            ->date(),
                    ])->columns(2),
            ])
            ->columns(1);
    }

    public static function getRelations(): array
    {
        return [
            PaymentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListInstallments::route('/'),
            'view' => ViewInstallment::route('/{record}'),
        ];
    }
}
