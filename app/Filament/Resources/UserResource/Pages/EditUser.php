<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $record = $this->getRecord();
        $plan = $data['plan'] ?? $record->plan;

        if ($plan !== $record->plan) {
            $definition = $record->planDefinition($plan);

            $data['plan_started_at'] = now();

            if (! array_key_exists('tournaments_quota', $data) || is_null($data['tournaments_quota'])) {
                $data['tournaments_quota'] = $definition['tournaments_quota'] ?? null;
            }
        }

        return $data;
    }
}
