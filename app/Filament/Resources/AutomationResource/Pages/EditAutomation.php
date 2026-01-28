<?php

namespace App\Filament\Resources\AutomationResource\Pages;

use App\Filament\Resources\AutomationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Http;

class EditAutomation extends EditRecord
{
    protected static string $resource = AutomationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('get_webhook_url')
                ->label('Get Webhook URL')
                ->icon('heroicon-o-link')
                ->visible(fn () => $this->record->trigger_type === 'webhook')
                ->action(function () {
                    $response = Http::post(config('app.url') . "/api/automations/{$this->record->id}/webhook-url");
                    $data = $response->json();

                    Notification::make()
                        ->title('Webhook URL')
                        ->body($data['webhook_url'] ?? 'Error fetching webhook URL')
                        ->success()
                        ->send();
                }),
            Actions\DeleteAction::make(),
        ];
    }
}
