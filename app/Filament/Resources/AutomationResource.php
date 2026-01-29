<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AutomationResource\Pages;
use App\Models\Automation;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Schemas\Schema;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Http;

class AutomationResource extends Resource
{
    protected static ?string $model = Automation::class;

    // Final fix for navigation icon type hints
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-cpu-chip';

    public static function form(Schema $form): Schema
    {
        return $form
            ->components([
                Forms\Components\Select::make('shop_id')
                    ->relationship('shop', 'name')
                    ->required(),
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('status')
                    ->options([
                        Automation::STATUS_ACTIVE => 'Active',
                        Automation::STATUS_INACTIVE => 'Inactive',
                    ])
                    ->required(),
                Forms\Components\Select::make('trigger_type')
                    ->options([
                        Automation::TRIGGER_WEBHOOK => 'Webhook',
                        Automation::TRIGGER_SCHEDULE => 'Schedule',
                        Automation::TRIGGER_MANUAL => 'Manual',
                        Automation::TRIGGER_PLAYGROUND => 'Playground',
                    ])
                    ->required()
                    ->reactive(),
                Forms\Components\KeyValue::make('trigger_config')
                    ->visible(fn ($get) => in_array($get('trigger_type'), ['webhook', 'schedule'])),
                Forms\Components\Repeater::make('steps')
                    ->schema([
                        Forms\Components\TextInput::make('id')
                            ->required(),
                        Forms\Components\TextInput::make('name')
                            ->required(),
                        Forms\Components\Select::make('action_type')
                            ->options([
                                'shopify.order.get' => 'Shopify: Get Order',
                                'shopify.order.add_tags' => 'Shopify: Add Tags',
                                'shopify.order.remove_tags' => 'Shopify: Remove Tags',
                                'recharge.subscription.get' => 'Recharge: Get Subscription',
                                'recharge.subscription.update' => 'Recharge: Update Subscription',
                            ])
                            ->required(),
                        Forms\Components\Toggle::make('enabled')
                            ->default(true),
                        Forms\Components\KeyValue::make('input_map'),
                        Forms\Components\KeyValue::make('config'),
                    ])
                    ->defaultItems(1)
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('shop.name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge(),
                Tables\Columns\TextColumn::make('trigger_type')
                    ->badge(),
                Tables\Columns\TextColumn::make('version'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        Automation::STATUS_ACTIVE => 'Active',
                        Automation::STATUS_INACTIVE => 'Inactive',
                    ]),
                Tables\Filters\SelectFilter::make('trigger_type')
                    ->options([
                        Automation::TRIGGER_WEBHOOK => 'Webhook',
                        Automation::TRIGGER_SCHEDULE => 'Schedule',
                        Automation::TRIGGER_MANUAL => 'Manual',
                        Automation::TRIGGER_PLAYGROUND => 'Playground',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('webhook_url')
                    ->label('Get Webhook URL')
                    ->icon('heroicon-o-link')
                    ->visible(fn (Automation $record) => $record->trigger_type === Automation::TRIGGER_WEBHOOK)
                    ->action(function (Automation $record) {
                        $response = Http::post(config('app.url') . "/api/automations/{$record->id}/webhook-url");
                        $data = $response->json();

                        Notification::make()
                            ->title('Webhook URL')
                            ->body($data['webhook_url'] ?? 'Error fetching webhook URL')
                            ->success()
                            ->send();
                    }),
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
            'index' => Pages\ListAutomations::route('/'),
            'create' => Pages\CreateAutomation::route('/create'),
            'edit' => Pages\EditAutomation::route('/{record}/edit'),
        ];
    }
}
