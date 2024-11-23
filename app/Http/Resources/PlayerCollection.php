<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class PlayerCollection extends ResourceCollection
{
	/**
	 * Transform the resource collection into an array.
	 *
	 * @return array<int|string, mixed>
	 */
	public function toArray(Request $request): array
	{
		return $this->collection->map(function ($player) {
			return [
				'id' => $player->id,
				'full_name' => $player->user->full_name,
				'birthdate' => [
					'date' => $player->birthdate->format('d-M-y'),
					'age' => $player->birthdate->age
				],
				'role' => [
					'id' => $player->user->roles->first()->id,
					'name' => $player->user->roles->first()->name,
				],
				'nationality' => $player->nationality,
				'image' => $player->user->image,
				'team' => [
					'id' => $player->team?->id,
					'name' => $player->team?->name,
				],
				'category' => [
					'id' => $player->category?->id,
					'name' => $player->category?->name,
				],
				'position' => [
					'id' => $player->position?->id,
					'name' => $player->position?->name,
					'abbr' => $player->position?->abbr,
				],
				'number' => $player->number,
				'height' => $player->height,
				'weight' => $player->weight,
			];
		})->toArray();
	}
}
