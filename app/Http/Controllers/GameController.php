<?php

namespace App\Http\Controllers;

use App\Http\Resources\GameResource;
use App\Models\Game;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class GameController extends Controller
{
    public const TWO_HOURS = 120;
    public const ONE_HOUR = 60;

    public function show(int $gameId): GameResource
    {
        $game = Game::with(["tournament.locations.fields"])->findOrFail($gameId);
        return new GameResource($game);
    }

    public function update(Request $request, Game $game): GameResource
    {
        $data = $request->validate([
            'date' => 'required|date',
            'selected_time' => 'required|array',
            'selected_time.*' => 'required|date_format:H:i',
            'field_id' => 'required|exists:fields,id',
            'day' => 'required|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
        ]);
        $tournament = $game->tournament;
        $currentGameMatchTime = $game->match_time;
        $currentGameFieldId = $game->field_id;
        $nextCurrentGameMatchTime = Carbon::parse($currentGameMatchTime)->addMinutes(60)->format('H:i');
        $startDate = $data['selected_time']['start'];
        $endDate = Carbon::parse($data['selected_time']['end'])->subMinutes(60)->format('H:i');
        $fieldId = $data['field_id'];
        $day = $data['day'];
        $tournamentField = $tournament->tournamentFields()->where('field_id', $currentGameFieldId)->firstOrFail();
        $availability = $tournamentField->availability[$day]['intervals'] ?? [];
        $takenIntervals = $availability;
        foreach ($takenIntervals as &$interval) {
            // Liberar el bloque antiguamente reservado:
            if ($interval['value'] === $currentGameMatchTime || $interval['value'] === $nextCurrentGameMatchTime) {
                $interval['selected'] = false;
                $interval['in_use'] = false;
            }
            if ($interval['value'] === $endDate || $interval['value'] === $endDate) {
                $interval['selected'] = true;
                $interval['in_use'] = true;
            }
        }
        unset($interval);
        // vuelves a guardar el array modificado en availability
        $tournamentField->availability[$day]['intervals'] = $takenIntervals;
        $tournamentField->availability = $availability;
        $tournamentField->save();
        $game->update([
            'match_date' => Carbon::parse($data['date'])->toDateString(),
            'match_time' => $data['selected_time']['start'] . ':00',
            'field_id' => $data['field_id'],
        ]);

        return new GameResource($game);
    }
}
