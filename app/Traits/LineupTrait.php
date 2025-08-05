<?php

namespace App\Traits;

use App\Models\Lineup;
use App\Models\LineupPlayer;
use function PHPUnit\Framework\isEmpty;

trait LineupTrait
{
    public function initDefaultLineupPlayers(Lineup $lineup): void
    {
        $lineup->load(['defaultLineup.defaultLineupPlayers','team.players']);
        $defaultLineupPlayers =  $lineup->defaultLineup?->defaultLineupPlayers;
        if (!isEmpty($defaultLineupPlayers)) {
            foreach ($defaultLineupPlayers as $defaultPLayer) {
                // todo DON'T REGISTER players IN LINEUP_PLAYERS table if it's in SUSPEND STATUS
                $lineup->lineupPlayers()->save(
                    LineupPlayer::updateOrCreate([
                        'lineup_id' => $lineup->id,
                        'player_id' => $defaultPLayer->player_id,
                    ], [
                        'is_headline' => false,
                        'field_location' => $defaultPLayer->field_location,
                        'substituted' => false,
                    ])
                );
            }
        }else{
            $lineup->team?->players?->each(function($player, $key) use($lineup){
                LineupPlayer::updateOrCreate([
                    'lineup_id' => $lineup->id,
                    'player_id' => $player->id,
                ], [
                    'is_headline' => false,
                    'field_location' => $key + 1, // without field_location
                    'substituted' => false,
                ]);
            });
        }
    }

}