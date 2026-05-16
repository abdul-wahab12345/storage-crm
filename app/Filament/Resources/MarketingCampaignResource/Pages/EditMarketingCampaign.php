<?php

namespace App\Filament\Resources\MarketingCampaignResource\Pages;

use App\Filament\Resources\MarketingCampaignResource;
use Filament\Resources\Pages\EditRecord;

class EditMarketingCampaign extends EditRecord
{
    protected static string $resource = MarketingCampaignResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
