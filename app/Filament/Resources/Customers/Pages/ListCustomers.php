<?php

namespace App\Filament\Resources\Customers\Pages;

use Filament\Actions\CreateAction;
use Filament\Support\Enums\Width;
use Filament\Schemas\Components\Tabs\Tab;
use App\Filament\Resources\Customers\CustomerResource;
use App\Models\Customer;
use App\Models\CustomerLevel;
use App\Models\Reseller;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListCustomers extends ListRecords
{
    protected static string $resource = CustomerResource::class;

    public function getTitle(): string
    {
        return __('admin/customer-resource.pages.list.title');
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->modalWidth(Width::Large),
        ];
    }

    public function getTabs(): array
    {

        $tabs = [
            'Semua' => Tab::make()->label(__('admin/customer-resource.tabs.all'))
                ->modifyQueryUsing(fn(Builder $query): Builder => $query->normalUser())
            // ->badge(Customer::normalUser()->count()),
        ];

        //  @feature-toggle: reseller - uncomment to ativate the reselller level tab
        // $resellers = Reseller::active()->orderBy('level', 'ASC')->get();

        // foreach ($resellers as $resel) {
        //     $tabs[$resel->name] = Tab::make()
        //         ->label($resel->name)
        //         ->modifyQueryUsing(fn (Builder $query): Builder => $query->resellerUser($resel->id))
        //         ->badge(Customer::resellerUser($resel->id)->count());
        // }

        $levels = CustomerLevel::active()->orderBy('id', 'ASC')->get();

        foreach ($levels as $key => $level) {
            $tabs[$level->slug] = Tab::make()
                ->label($level->name)
                ->modifyQueryUsing(fn(Builder $query): Builder => $query->customerLevel($level->id));
        }

        return $tabs;
    }
}
