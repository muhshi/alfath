<?php

namespace App\Filament\Resources\SurveyResource\Pages;

use App\Filament\Resources\SurveyResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSurvey extends EditRecord
{
    protected static string $resource = SurveyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\Action::make('new')
                ->label('New')
                ->icon('heroicon-o-plus')
                ->url(fn() => SurveyResource::getUrl('create'))
                ->color('primary'),
        ];
    }
}
