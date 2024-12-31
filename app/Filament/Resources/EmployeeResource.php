<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EmployeeResource\Pages;
use App\Models\City;
use App\Models\Employee;
use App\Models\State;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class EmployeeResource extends Resource
{
    protected static ?string $model = Employee::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationGroup = 'Employee Management';

    protected static ?string $recordTitleAttribute = 'name';

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'country.name'];
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'Nick Name' => $record->nick_name
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Employee Name')
                    ->description('Put your employee name here...')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('nick_name')
                            ->required()
                            ->maxLength(255),
                    ])->columns(2),

                Section::make('Employee Address')
                    ->schema([
                        TextInput::make('address')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('zip_code')
                            ->required()
                            ->maxLength(255),
                        Select::make('country_id')
                            ->relationship(name: 'country', titleAttribute: 'name')
                            ->live()
                            ->afterStateUpdated(function (Set $set) {
                                $set('state_id', null);
                                $set('city_id', null);
                            })
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('state_id')
                            ->options(fn(Get $get): Collection => State::query()
                                ->where('country_id', $get('country_id'))
                                ->pluck('name', 'id'))
                            ->live()
                            ->afterStateUpdated(fn(Set $set) => $set('city_id', null))
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('city_id')
                            ->options(fn(Get $get): Collection => City::query()
                                ->where('state_id', $get('state_id'))
                                ->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('department_id')
                            ->relationship(name: 'department', titleAttribute: 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                    ])->columns(2),

                Section::make('Dates')
                    ->schema([
                        DatePicker::make('bod')
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->required(),
                        DatePicker::make('hired_date')
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->required()
                    ])->columns(2)
                // Forms\Components\TextInput::make('country_id')
                //     ->required()
                //     ->numeric(),
                // Forms\Components\TextInput::make('state_id')
                //     ->required()
                //     ->numeric(),
                // Forms\Components\TextInput::make('city_id')
                //     ->required()
                //     ->numeric(),
                // Forms\Components\TextInput::make('department_id')
                //     ->required()
                //     ->numeric(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('country.name')
                    ->label('Country'),
                // ->sortable(),
                Tables\Columns\TextColumn::make('state.name')
                    ->label('State'),
                // ->sortable(),
                Tables\Columns\TextColumn::make('city.name')
                    ->label('City'),
                // ->sortable(),
                Tables\Columns\TextColumn::make('department.name')
                    ->label('Department'),
                // ->sortable(),
                Tables\Columns\TextColumn::make('address')
                    ->searchable(),
                Tables\Columns\TextColumn::make('bod')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('hired_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('zip_code')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('Department')
                    ->relationship('department', 'name')
                    ->label('Filter By Department')
                    ->indicator('Department')
                    ->searchable()
                    ->preload(),
                Filter::make('created_at')
                    ->form([
                        DatePicker::make('from')->label('From Date'),
                        DatePicker::make('to')->label('To Date')
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['from'],
                            fn(Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date)
                        )
                            ->when(
                                $data['to'],
                                fn(Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date)
                            );
                    })->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['from'] ?? null) $indicators['from'] = 'From ' . Carbon::parse($data['from'])->format('d/m/Y');
                        if ($data['to'] ?? null) $indicators['to'] = 'To ' . Carbon::parse($data['to'])->format('d/m/Y');

                        return $indicators;
                    })
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
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
            'index' => Pages\ListEmployees::route('/'),
            'create' => Pages\CreateEmployee::route('/create'),
            'view' => Pages\ViewEmployee::route('/{record}'),
            'edit' => Pages\EditEmployee::route('/{record}/edit'),
        ];
    }
}
