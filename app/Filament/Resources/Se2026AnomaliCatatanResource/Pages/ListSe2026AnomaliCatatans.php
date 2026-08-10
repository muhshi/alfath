<?php

namespace App\Filament\Resources\Se2026AnomaliCatatanResource\Pages;

use App\Filament\Resources\Se2026AnomaliCatatanResource;
use Filament\Resources\Pages\ListRecords;

class ListSe2026AnomaliCatatans extends ListRecords
{
    protected static string $resource = Se2026AnomaliCatatanResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
