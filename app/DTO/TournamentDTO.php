<?php

namespace App\DTO;

class TournamentDTO
{
    public array $basic;
    public ?array $details;
    public bool $hasLocation = false;
    public array $locationIds;


    /**
     * @throws \JsonException
     */
    public function __construct(array $formRequest)
    {
        $this->basic = $formRequest['basic'];
        if (isset($formRequest['details'])) {
            $this->details = $formRequest['details'];
            $this->hasLocation = isset($formRequest['details']['locationIds']);
            if ($this->hasLocation) {
                $this->locationIds = json_decode($formRequest['details']['locationIds'], true, 512, JSON_THROW_ON_ERROR);
            }
        }
    }

    public function toArray(): array
    {
        return [
            'basic' => $this->basic,
            'details' => $this->details,
        ];
    }

    public function basicFields(): array
    {
        return [
            'name' => $this->basic['name'],
            'min_max' => $this->basic['min_max'] ?? null,
            'tournament_format_id' => $this->basic['tournament_format_id'],
            'substitutions_per_team' => $this->basic['substitutions_per_team'],
            'category_id' => $this->basic['category_id'],
            'football_type_id' => $this->basic['football_type_id'],
            'start_date' => $this->basic['start_date'] ?? null,
            'end_date' => $this->basic['end_date'] ?? null,
            'prize' => $this->details['prize'] ?? null,
            'winner' => $this->details['winner'] ?? null,
            'description' => $this->details['description'] ?? null,
        ];
    }

    public function locationFields(): array
    {
        return $this->locationIds;
    }

    public function getImage()
    {
        return $this->basic['image'];
    }
}
