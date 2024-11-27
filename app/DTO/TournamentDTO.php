<?php

namespace App\DTO;

class TournamentDTO
{
	public array $basic;
	public ?array $details;
	public bool $hasLocation = false;
	public array $location;


	public function __construct(array $formRequest)
	{
		$this->basic = $formRequest['basic'];
		if (isset($formRequest['details'])) {
			$this->details = $formRequest['details'];
			$this->hasLocation = isset($formRequest['details']['location']);
			if ($this->hasLocation) {
				$this->location = json_decode($formRequest['details']['location'], true);
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
			'tournament_format_id' => $this->basic['tournament_format_id'],
			'category_id' => $this->basic['category_id'],
			'football_type_id' => $this->basic['football_type_id'],
			'start_date' => $this->details['start_date'] ?? null,
			'end_date' => $this->details['end_date'] ?? null,
			'prize' => $this->details['prize'] ?? null,
			'winner' => $this->details['winner'] ?? null,
			'description' => $this->details['description'] ?? null,
		];
	}

	public function locationFields(): array
	{
		return [
			'name' => $this->location['structured_formatting']['main_text'] ?? null,
			'address' => $this->location['description'] ?? null,
			'city' => $this->location['terms'][2]['value'] ?? null,
			'autocomplete_prediction' => $this->location ?? null
		];
	}

	public function getImage()
	{
		return $this->basic['image'];
	}
}
