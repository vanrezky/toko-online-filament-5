<?php

namespace App\Filament\Resources\Transactions\Pages;

use Filament\Actions\Action;
use Exception;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Infolists\Components\RepeatableEntry;
use App\Enums\CourierCode;
use App\Enums\TransactionBillingStatus;
use App\Enums\TransactionStatus;
use App\Filament\Resources\Transactions\TransactionResource;
use App\Models\Transaction;
use Filament\Actions;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\DB;

class ViewTransaction extends ViewRecord
{
    protected static string $resource = TransactionResource::class;

    public function getTitle(): string
    {
        return __('admin/transaction-resource.pages.view.title');
    }

    protected function getHeaderActions(): array
    {
        return [
            ...$this->getStatusActions(),
            ...$this->getBillingActions(),
        ];
    }

    protected function getStatusActions(): array
    {
        $record = $this->getRecord();
        $actions = [];
        $isPickupOrder = $this->isPickupOrder($record);

        switch ($record->status) {
            case TransactionStatus::packed:
                if ($isPickupOrder) {
                    $actions[] = Action::make('markPickedUp')
                        ->label(__('admin/transaction-resource.actions.mark_as_picked_up'))
                        ->icon('heroicon-o-hand-raised')
                        ->color('info')
                        ->requiresConfirmation()
                        ->action(fn() => $this->updateStatus('picked_up'));
                } else {
                    $actions[] = Action::make('markInTransit')
                        ->label(__('admin/transaction-resource.actions.mark_as_shipped'))
                        ->icon('heroicon-o-truck')
                        ->color('primary')
                        ->requiresConfirmation()
                        ->action(fn() => $this->updateStatus('in_transit'));
                }
                break;

            case TransactionStatus::in_transit:
            case TransactionStatus::shipped:
                $actions[] = Action::make('markDelivered')
                    ->label(__('admin/transaction-resource.actions.mark_as_delivered'))
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(fn() => $this->updateStatus('delivered'));
                break;

            case TransactionStatus::picked_up:
                $actions[] = Action::make('markCompletedFromPickup')
                    ->label(__('admin/transaction-resource.actions.mark_as_completed'))
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(fn() => $this->updateStatus('completed'));
                break;

            case TransactionStatus::delivered:
                $actions[] = Action::make('markCompleted')
                    ->label(__('admin/transaction-resource.actions.mark_as_completed'))
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(fn() => $this->updateStatus('completed'));
                break;
        }

        return $actions;
    }

    protected function updateStatus(string $status): void
    {
        $record = $this->getRecord();

        $updateData = ['status' => $status];

        if (in_array($status, ['in_transit', 'picked_up'], true)) {
            $updateData['delivery_date'] = now();
        } elseif ($status === 'completed') {
            $updateData['complete_date'] = now();
        }

        DB::beginTransaction();
        try {
            $record->update($updateData);
            DB::commit();

            Notification::make()
                ->title(__('admin/transaction-resource.notifications.status_updated'))
                ->body(__('admin/transaction-resource.notifications.status_changed_to') . " " . __("admin/transaction-resource.status.{$status}"))
                ->success()
                ->send();

            $this->refreshCurrentRecordView();
        } catch (Exception $e) {
            DB::rollBack();

            Notification::make()
                ->title(__('admin/transaction-resource.notifications.update_failed'))
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    protected function getBillingActions(): array
    {
        $record = $this->getRecord();

        if ($record->payment_type !== 'full') {
            return [];
        }

        $actions = [];

        if ($record->billing_status === 'pending' || $record->billing_status === 'failed') {
            $actions[] = Action::make('markBillingSubmitted')
                ->label('Tandai Tagihan Diajukan')
                ->icon('heroicon-o-paper-airplane')
                ->color('warning')
                ->requiresConfirmation()
                ->action(fn() => $this->updateBillingStatus('submitted'));
        }

        if ($record->billing_status === 'submitted') {
            $actions[] = Action::make('markBillingPaid')
                ->label('Tandai Tagihan Lunas')
                ->icon('heroicon-o-check-badge')
                ->color('success')
                ->requiresConfirmation()
                ->action(fn() => $this->updateBillingStatus('paid'));

            $actions[] = Action::make('markBillingFailed')
                ->label('Tandai Tagihan Gagal')
                ->icon('heroicon-o-exclamation-triangle')
                ->color('danger')
                ->requiresConfirmation()
                ->action(fn() => $this->updateBillingStatus('failed'));
        }

        return $actions;
    }

    protected function updateBillingStatus(string $status): void
    {
        $record = $this->getRecord();

        DB::beginTransaction();
        try {
            $record->update([
                'billing_status' => $status,
            ]);

            DB::commit();

            Notification::make()
                ->title('Status tagihan berhasil diperbarui')
                ->body('Status tagihan sekarang: ' . $status)
                ->success()
                ->send();

            $this->refreshCurrentRecordView();
        } catch (Exception $e) {
            DB::rollBack();

            Notification::make()
                ->title(__('admin/transaction-resource.notifications.update_failed'))
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    protected function refreshCurrentRecordView(): void
    {
        $this->redirect(
            static::getResource()::getUrl('view', ['record' => $this->getRecord()]),
            navigate: true,
        );
    }

    protected function isPickupOrder(Transaction $record): bool
    {
        $record->loadMissing('shippingDetails');

        if ($record->shippingDetails->isEmpty()) {
            return false;
        }

        return $record->shippingDetails->every(
            fn($detail) => strtoupper((string) $detail->courier_code) === CourierCode::PICKUP->value
        );
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make(__('admin/transaction-resource.sections.order_information'))
                    ->icon('heroicon-o-shopping-bag')
                    ->schema([
                        TextEntry::make('uuid')
                            ->label(__('admin/transaction-resource.entries.order_id'))
                            ->getStateUsing(fn(Transaction $record): string => $record->code ?? '-')
                            ->copyable()
                            ->copyMessage(__('admin/transaction-resource.copy.copied'))
                            ->copyMessageDuration(1500)
                            ->weight('bold')
                            ->size('lg'),
                        TextEntry::make('status')
                            ->label(__('admin/transaction-resource.entries.status'))
                            ->badge()
                            ->formatStateUsing(function (TransactionStatus|string|null $state): string {
                                if ($state instanceof TransactionStatus) {
                                    return (string) $state->getLabel();
                                }

                                if (is_string($state) && $state !== '') {
                                    return ucfirst(__("admin/transaction-resource.status.{$state}"));
                                }

                                return '-';
                            }),
                        TextEntry::make('created_at')
                            ->label(__('admin/transaction-resource.entries.order_date'))
                            ->dateTime('d M Y, H:i'),
                        // TextEntry::make('timelimit')
                        //     ->label(__('admin/transaction-resource.entries.payment_deadline'))
                        //     ->dateTime('d M Y, H:i')
                        //     ->placeholder(__('admin/transaction-resource.entries.not_set')),
                    ])->columns(3),

                Section::make(__('admin/transaction-resource.sections.order_summary'))
                    ->icon('heroicon-o-currency-dollar')
                    ->schema([
                        TextEntry::make('subtotal')
                            ->label(__('admin/transaction-resource.entries.subtotal'))
                            ->money('IDR'),
                        TextEntry::make('total_discount')
                            ->label(__('admin/transaction-resource.entries.total_discount'))
                            ->money('IDR')
                            ->color('danger'),
                        TextEntry::make('shipping_cost')
                            ->label(__('admin/transaction-resource.entries.shipping_cost'))
                            ->money('IDR'),
                        TextEntry::make('cod_fee')
                            ->label(__('admin/transaction-resource.entries.cod_fee'))
                            ->money('IDR')
                            ->visible(fn(Transaction $record) => $record->cod),
                        TextEntry::make('total_amount')
                            ->label(__('admin/transaction-resource.entries.total_amount'))
                            ->money('IDR')
                            ->weight('bold')
                            ->size('lg')
                            ->color('primary'),
                    ])->columns(5),

                Section::make(__('admin/transaction-resource.sections.customer_information'))
                    ->icon('heroicon-o-user')
                    ->schema([
                        TextEntry::make('customer.full_name')
                            ->label(__('admin/transaction-resource.entries.name'))
                            ->icon('heroicon-o-user')
                            ->iconColor('primary'),
                        TextEntry::make('customer.email')
                            ->label(__('admin/transaction-resource.entries.email'))
                            ->icon('heroicon-o-envelope')
                            ->iconColor('primary'),
                        TextEntry::make('customer.phone')
                            ->label(__('admin/transaction-resource.entries.phone'))
                            ->icon('heroicon-o-phone')
                            ->iconColor('primary'),
                        TextEntry::make('address.full_address')
                            ->label(__('admin/transaction-resource.entries.shipping_address'))
                            ->icon('heroicon-o-map-pin')
                            ->iconColor('primary')
                            ->visible(function (Transaction $record): bool {
                                $details = $record->shippingDetails;

                                if ($details->isEmpty()) {
                                    return false;
                                }

                                return ! $details->every(
                                    fn($detail) => strtoupper((string) $detail->courier_code) === CourierCode::PICKUP->value
                                );
                            })
                            ->getStateUsing(function (Transaction $record): string {
                                $details = $record->shippingDetails;

                                if ($details->isNotEmpty() && $details->every(fn($detail) => strtoupper((string) $detail->courier_code) === CourierCode::PICKUP->value)) {
                                    return __('admin/transaction-resource.columns.pickup_no_address');
                                }

                                if (! $record->address) {
                                    return __('admin/transaction-resource.entries.not_set');
                                }

                                return implode(', ', array_filter([
                                    $record->address->address,
                                    $record->address->village?->name,
                                    $record->address->subDistrict?->name,
                                    $record->address->district?->name,
                                    $record->address->province?->name,
                                    $record->address->postal_code,
                                ]));
                            }),
                    ])
                    ->columns(2),

                Section::make(__('admin/transaction-resource.sections.products'))
                    ->icon('heroicon-o-shopping-cart')
                    ->description(fn(Transaction $record) => $record->total_items . ' ' . strtolower(__('admin/transaction-resource.columns.items')))
                    ->schema([
                        RepeatableEntry::make('products')
                            ->schema([
                                TextEntry::make('product.name')
                                    ->label(__('admin/transaction-resource.entries.product')),
                                TextEntry::make('quantity')
                                    ->label(__('admin/transaction-resource.entries.qty')),
                                TextEntry::make('price')
                                    ->label(__('admin/transaction-resource.entries.price'))
                                    ->money('IDR'),
                                TextEntry::make('discount')
                                    ->label(__('admin/transaction-resource.entries.discount'))
                                    ->money('IDR')
                                    ->color('danger'),
                                TextEntry::make('subtotal')
                                    ->label(__('admin/transaction-resource.entries.subtotal'))
                                    ->money('IDR')
                                    ->weight('bold'),
                                IconEntry::make('is_digital')
                                    ->label(__('admin/transaction-resource.entries.digital'))
                                    ->boolean()
                                    ->trueIcon('heroicon-m-cloud-arrow-down')
                                    ->falseIcon(null)
                                    ->trueColor('info'),
                            ])->columns(6),
                    ]),

                Section::make(__('admin/transaction-resource.sections.shipping_information'))
                    ->icon('heroicon-o-truck')
                    ->schema([
                        TextEntry::make('shipping_method')
                            ->label(__('admin/transaction-resource.entries.courier'))
                            ->getStateUsing(function (Transaction $record): string {
                                $details = $record->shippingDetails;

                                if ($details->isEmpty()) {
                                    return __('admin/transaction-resource.entries.not_set');
                                }

                                if ($details->every(fn($detail) => strtoupper((string) $detail->courier_code) === CourierCode::PICKUP->value)) {
                                    return __('admin/transaction-resource.columns.pickup_only');
                                }

                                return $details
                                    ->pluck('courier_name')
                                    ->filter()
                                    ->unique()
                                    ->implode(', ');
                            }),
                        TextEntry::make('receipt_code')
                            ->label(__('admin/transaction-resource.entries.receipt_code'))
                            ->copyable()
                            ->placeholder(__('admin/transaction-resource.entries.not_set')),
                        TextEntry::make('weight')
                            ->label(__('admin/transaction-resource.entries.weight'))
                            ->suffix(' gram'),
                        RepeatableEntry::make('shippingDetails')
                            ->label(__('admin/transaction-resource.sections.shipping_information'))
                            ->schema([
                                TextEntry::make('warehouse.name')
                                    ->label(__('admin/transaction-resource.entries.warehouse'))
                                    ->placeholder(__('admin/transaction-resource.entries.not_set')),
                                TextEntry::make('courier_name')
                                    ->label(__('admin/transaction-resource.entries.courier')),
                                TextEntry::make('courier_code')
                                    ->label(__('admin/transaction-resource.entries.code'))
                                    ->badge(),
                                TextEntry::make('price')
                                    ->label(__('admin/transaction-resource.entries.shipping_cost'))
                                    ->money('IDR'),
                                TextEntry::make('weight')
                                    ->label(__('admin/transaction-resource.entries.weight'))
                                    ->suffix(' gram'),
                                TextEntry::make('estimation')
                                    ->label(__('admin/transaction-resource.entries.estimation'))
                                    ->placeholder(__('admin/transaction-resource.entries.not_set')),
                            ])
                            ->columns(6)
                            ->columnSpanFull()
                            ->contained(false),
                    ])->columns(3),

                Section::make(__('admin/transaction-resource.sections.vouchers_applied'))
                    ->icon('heroicon-o-ticket')
                    ->visible(fn(Transaction $record) => $record->vouchers->count() > 0)
                    ->schema([
                        RepeatableEntry::make('vouchers')
                            ->schema([
                                TextEntry::make('voucher_name')
                                    ->label(__('admin/transaction-resource.entries.voucher')),
                                TextEntry::make('voucher_type')
                                    ->label(__('admin/transaction-resource.entries.type'))
                                    ->badge()
                                    ->formatStateUsing(fn(string $state) => ucfirst($state)),
                                TextEntry::make('formatted_discount')
                                    ->label(__('admin/transaction-resource.entries.discount')),
                            ])->columns(3),
                    ]),

                Section::make(__('admin/transaction-resource.sections.digital_products'))
                    ->icon('heroicon-o-cloud-arrow-down')
                    ->visible(fn(Transaction $record) => $record->hasDigitalProducts())
                    ->schema([
                        TextEntry::make('digital_products_count')
                            ->label(__('admin/transaction-resource.entries.this_order_contains_digital_products'))
                            ->weight('bold')
                            ->color('info'),
                    ]),

                Section::make(__('admin/transaction-resource.sections.additional_information'))
                    ->icon('heroicon-o-information-circle')
                    ->schema([
                        TextEntry::make('cod')
                            ->label(__('admin/transaction-resource.entries.payment_method'))
                            ->formatStateUsing(fn(bool $state) => $state ? 'COD (Bayar di Tempat)' : 'Transfer')
                            ->badge()
                            ->color(fn(bool $state) => $state ? 'warning' : 'info'),
                        TextEntry::make('payment_method')
                            ->label(__('admin/transaction-resource.entries.payment_gateway'))
                            ->placeholder(__('admin/transaction-resource.entries.not_set')),
                        TextEntry::make('payment_type')
                            ->label('Jenis Pembayaran')
                            ->badge()
                            ->formatStateUsing(fn(?string $state): string => $state === 'installment' ? 'Cicilan' : 'Penuh'),
                        TextEntry::make('billing_status')
                            ->label('Status Tagihan')
                            ->badge()
                            ->visible(fn(Transaction $record): bool => $record->payment_type === 'full')
                            ->color(function (TransactionBillingStatus|string|null $state): string {
                                if ($state instanceof TransactionBillingStatus) {
                                    return is_array($state->getColor()) ? 'gray' : (string) ($state->getColor() ?? 'gray');
                                }

                                if (! is_string($state) || $state === '') {
                                    return 'gray';
                                }

                                return match ($state) {
                                    'pending' => 'warning',
                                    'submitted' => 'info',
                                    'paid' => 'success',
                                    'failed', 'cancelled' => 'danger',
                                    default => 'gray',
                                };
                            })
                            ->formatStateUsing(function (TransactionBillingStatus|string|null $state): string {
                                if ($state instanceof TransactionBillingStatus) {
                                    return (string) $state->getLabel();
                                }

                                if (is_string($state) && $state !== '') {
                                    return ucfirst(__("admin/transaction-resource.billing_status.{$state}"));
                                }

                                return '-';
                            }),
                        TextEntry::make('billing_due_date')
                            ->label('Jatuh Tempo Tagihan')
                            ->date('d M Y')
                            ->visible(fn(Transaction $record): bool => $record->payment_type === 'full')
                            ->placeholder(__('admin/transaction-resource.entries.not_set')),
                        TextEntry::make('request_cancellation')
                            ->label(__('admin/transaction-resource.entries.cancellation_request'))
                            ->formatStateUsing(fn(bool $state) => $state ? __('Yes') : __('No'))
                            ->color(fn(bool $state) => $state ? 'danger' : 'gray'),
                        TextEntry::make('notes')
                            ->label(__('admin/transaction-resource.entries.notes'))
                            ->placeholder(__('admin/transaction-resource.entries.no_notes')),
                        TextEntry::make('delivery_date')
                            ->label(__('admin/transaction-resource.entries.delivery_date'))
                            ->dateTime('d M Y, H:i')
                            ->placeholder(__('admin/transaction-resource.entries.not_delivered_yet')),
                        TextEntry::make('complete_date')
                            ->label(__('admin/transaction-resource.entries.completed_date'))
                            ->dateTime('d M Y, H:i')
                            ->placeholder(__('admin/transaction-resource.entries.not_completed_yet')),
                    ])->columns(2),
            ])->columns(1);
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $this->record->load([
            'customer',
            'address.province',
            'address.district',
            'address.subDistrict',
            'address.village',
            'products.product',
            'vouchers',
            'shippingDetails.warehouse',
        ]);

        return $data;
    }
}
