<?php

namespace App\Filament\Resources\MarketingCampaignResource\Pages;

use App\Filament\Resources\MarketingCampaignResource;
use Filament\Resources\Pages\CreateRecord;

class CreateMarketingCampaign extends CreateRecord
{
    protected static string $resource = MarketingCampaignResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();
        $data['status']     = 'draft';
        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
