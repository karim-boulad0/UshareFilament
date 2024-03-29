<?php

namespace App\Filament\Resources;

use Carbon\Carbon;
use Filament\Tables;
use App\Models\Bundle;
use App\Models\Subscription;
use Filament\Resources\Form;
use Filament\Resources\Table;
use Filament\Resources\Resource;
use Filament\Forms\Components\Card;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Request;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Actions\DeleteAction;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Columns\BooleanColumn;
use App\Filament\Resources\SubscriptionResource\Pages;
use App\Filament\Resources\SubscriptionResource\Widgets\StatsOverview;

class SubscriptionResource extends Resource
{

    protected static ?string $model = Subscription::class;
    protected static ?string $navigationIcon = 'heroicon-o-collection';
    protected static ?string $navigationGroup = 'Subscriptions';

    public static function getEloquentQuery(): Builder
    {
        if (auth()->user()->id && !auth()->user()->is_admin) {

            return static::getModel()::query()->where('user_id', auth()->user()->id)->orderBy("id", 'desc')
                ->whereIn('cycle_id', function ($query) {
                    $query->select('id')->from('cycles')->where('end_date', '>', now());
                });
        } else {
            if (auth()->user()->hasRole('show-archive')) {
                return static::getModel()::query()->orderBy("id", 'desc');
            } else {
                return static::getModel()::query()->orderBy("id", 'desc')->whereIn('cycle_id', function ($query) {
                    $query->select('id')->from('cycles')->where('end_date', '>', now());
                });
            }
        }
    }

    public static function form(Form $form): Form
    {
        if (auth()->user()->hasRole(['super-admin', 'admin'])) {
            return $form
                ->schema([
                    Card::make()
                        ->schema([
                            Select::make('user_id')
                                ->relationship('user', 'name')
                                ->default(fn (callable $set) => $set('user_id', auth()->user()->id))
                                ->disabled(),
                            Select::make('cycle_id')
                                ->required()
                                ->relationship('cycle', 'name', fn (Builder $query) => $query->where('end_date', '>', Carbon::now()))
                                ->getOptionLabelFromRecordUsing(fn (Model $record) => "{$record->name} : {$record->start_date->format('d/m/Y')} - {$record->end_date->format('d/m/Y')}"),
                            Select::make('bundle_id')
                                ->relationship('bundle', 'capacity')
                                ->options(function () {
                                    return Bundle::where('is_active', 1)->pluck('name', 'id')->sort();
                                }),
                            TextInput::make('phone_number'),
                            TextInput::make('verification_code')
                                ->maxLength(8)
                                ->nullable()
                                ->numeric(),
                            TextInput::make('note'),
                            TextInput::make('user_update')->hidden()->default(fn (callable $set) => $set('user_update', auth()->user()->id))
                                ->disabled(),
                            Toggle::make('is_approve')->default(0),
                        ])
                ]);
        } else {
            $isEditPage = strpos(Request::path(), 'edit') !== false;
            return $form
                ->schema([
                    Card::make()
                        ->schema([
                            Select::make('user_id')
                                ->relationship('user', 'name')
                                ->default(fn (callable $set) => $set('user_id', auth()->user()->id))
                                ->disabled(),
                            Select::make('bundle_id')

                                ->relationship('bundle', 'capacity')
                                ->options(function () {
                                    return Bundle::where('is_active', 1)->pluck('name', 'id')->sort();
                                })->hidden($isEditPage),
                            Select::make('cycle_id')
                                ->required()
                                ->relationship('cycle', 'name', fn (Builder $query) => $query->where('end_date', '>', Carbon::now()))
                                ->getOptionLabelFromRecordUsing(fn (Model $record) => "{$record->name} : {$record->start_date->format('d/m/Y')} - {$record->end_date->format('d/m/Y')}")->hidden($isEditPage),
                            TextInput::make('phone_number')->hidden($isEditPage),
                            TextInput::make('note')->hidden($isEditPage),
                            Toggle::make('is_paid')->default(null),
                            Toggle::make('is_approve')->hidden()->default(0),

                        ])
                ]);
        }
    }

    public static function table(Table $table): Table
    {
        if (auth()->user()->hasRole('super-admin', 'show-archive')) {

            return $table
                ->columns([
                    TextColumn::make('id')->sortable(),
                    TextColumn::make('user.name')->searchable(),
                    TextColumn::make('cycle.name')->searchable(),
                    TextColumn::make('bundle.name')->searchable(),
                    TextColumn::make('phone_number')->searchable(),
                    TextColumn::make('price'),
                    TextColumn::make('verification_code')->searchable(),
                    TextColumn::make('note'),
                    BooleanColumn::make('is_approve')->searchable(),

                ])

                ->filters([
                    Tables\Filters\Filter::make('NotApprove')
                        ->query(fn (Builder $query): Builder => $query->where('is_approve', 0)->orWhere('is_approve', null)),
                    Tables\Filters\Filter::make('Approve')
                        ->query(fn (Builder $query): Builder => $query->whereIn('is_approve', [1])),

                ])
                ->actions([
                    Tables\Actions\EditAction::make(),
                    DeleteAction::make(),
                ])
                ->bulkActions([
                    Tables\Actions\DeleteBulkAction::make(),
                ]);
        } else {
            return $table
                ->columns([
                    TextColumn::make('id')->sortable(),
                    TextColumn::make('user.name')->searchable(),
                    TextColumn::make('cycle.name')->searchable(),
                    TextColumn::make('bundle.name')->searchable(),
                    TextColumn::make('phone_number')->searchable(),
                    TextColumn::make('price'),
                    TextColumn::make('verification_code')->searchable(),
                    TextColumn::make('note'),
                    BooleanColumn::make('is_approve'),
                    BooleanColumn::make('is_paid'),

                ])

                ->filters([
                    Tables\Filters\Filter::make('NotApprove')
                        ->query(fn (Builder $query): Builder => $query->where('is_approve', 0)->orWhere('is_approve', null)),
                    Tables\Filters\Filter::make('Approve')
                        ->query(fn (Builder $query): Builder => $query->whereIn('is_approve', [1])),
                ])
                ->actions([
                    Tables\Actions\EditAction::make(),
                    DeleteAction::make(),
                ])
                ->bulkActions([
                    Tables\Actions\DeleteBulkAction::make(),
                ]);
        }
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
            'index' => Pages\ListSubscriptions::route('/'),
            'create' => Pages\CreateSubscription::route('/create'),
            'edit' => Pages\EditSubscription::route('/{record}/edit'),
        ];
    }
    public static function getWidgets(): array
    {
        return [
            StatsOverview::class,
        ];
    }
}
