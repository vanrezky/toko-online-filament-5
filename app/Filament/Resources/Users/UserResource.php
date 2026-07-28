<?php

namespace App\Filament\Resources\Users;

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\Pages\ViewUser;
use App\Models\User;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-users';

    protected static ?string $slug = 'setting/users';

    public static function getNavigationLabel(): string
    {
        return __('admin/user-resource.navigation_label');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('admin/user-resource.navigation_group');
    }

    public static function getModelLabel(): string
    {
        return __('admin/user-resource.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin/user-resource.plural_model_label');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('admin/user-resource.section'))
                    ->schema([
                        TextInput::make('name')
                            ->label(__('admin/user-resource.fields.name'))
                            ->required()
                            ->maxLength(255),
                        TextInput::make('email')
                            ->label(__('admin/user-resource.fields.email'))
                            ->email()
                            ->required()
                            ->minLength(5)
                            ->maxLength(50)
                            ->unique(ignoreRecord: true),
                        Select::make('roles')
                            ->label(__('admin/user-resource.fields.roles'))
                            ->relationship('roles', titleAttribute: 'name', modifyQueryUsing: fn ($query) => $query->whereNot('name', 'super_admin')),
                        TextInput::make('password')
                            ->label(__('admin/user-resource.fields.password'))
                            ->password()
                            ->revealable()
                            ->rules([securePassword()])
                            ->required()
                            ->maxLength(255)
                            ->minLength(8)
                            ->hiddenOn(['view', 'edit']),
                        TextInput::make('confirm_password')
                            ->label(__('admin/user-resource.fields.confirm_password'))
                            ->same('password')
                            ->password()
                            ->revealable()
                            ->required()
                            ->maxLength(255)
                            ->minLength(8)
                            ->hiddenOn(['view', 'edit']),
                    ])
                    ->columns(2),
            ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->superUser(false))
            ->columns([
                TextColumn::make('name')
                    ->label(__('admin/user-resource.columns.name'))
                    ->searchable(),
                TextColumn::make('email')
                    ->label(__('admin/user-resource.columns.email'))
                    ->searchable(),
                TextColumn::make('email_verified_at')
                    ->label(__('admin/user-resource.columns.email_verified_at'))
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label(__('admin/user-resource.columns.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label(__('admin/user-resource.columns.updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Filter::make('verified')
                    ->label(__('admin/user-resource.filters.verified'))
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('email_verified_at')),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    DeleteAction::make(),
                ]),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'view' => ViewUser::route('/{record}'),
            'edit' => EditUser::route('/{record}/edit'),

        ];
    }
}
