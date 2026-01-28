<?php

namespace App\Actions\Tournament;

use App\Models\GameEvent;
use App\Models\Tournament;
use Illuminate\Support\Facades\DB;

class GetTournamentStatsAction {
    public function execute(Tournament $tournament): array
    {
        $goals = DB::table('game_events')
            ->join('games','games.id','=','game_events.game_id')
            ->join('players','players.id','game_events.player_id')
            ->join('users','users.id','players.user_id')
            ->join('teams','teams.id','game_events.team_id')
            ->where('games.tournament_id', $tournament->id)
            ->whereIn('game_events.type',[GameEvent::GOAL, GameEvent::PENALTY])
            ->select(
                'players.id as player_id',
                'users.name as player_name',
                'teams.name as team_name',
                'teams.slug as team_slug',
                DB::raw("COALESCE(users.image, CONCAT('https://ui-avatars.com/api/?name=', REPLACE(users.name, ' ', '+'))) as user_image"),
                DB::raw("COALESCE(teams.image, CONCAT('https://ui-avatars.com/api/?name=', REPLACE(COALESCE(teams.slug, ''), ' ', '+'))) as team_image"),
                DB::raw('COUNT(*) as total' )
            )
            ->groupBy('players.id','users.name','teams.name','teams.image','teams.slug')
            ->orderByDesc('total')
            ->limit(5)
            ->get();
        $assistance =  DB::table('game_events')
            ->join('games','games.id','game_events.game_id')
            ->join('players','players.id','game_events.related_player_id')
            ->join('teams','teams.id','game_events.team_id')
            ->join('users','users.id','players.user_id')
            ->where('games.tournament_id', $tournament->id)
            ->where('game_events.type',GameEvent::GOAL)
            ->whereNotNull('game_events.related_player_id')
            ->select(
                'players.id as player_id',
                'users.name as player_name',
                'teams.name as team_name',
                DB::raw("COALESCE(users.image, CONCAT('https://ui-avatars.com/api/?name=', users.name)) as user_image"),
                DB::raw("COALESCE(teams.image, CONCAT('https://ui-avatars.com/api/?name=', REPLACE(COALESCE(teams.name, ''), ' ', '+'))) as team_image"),
                DB::raw('COUNT(*) as total' )
            )
            ->groupBy('players.id','users.name','teams.name','teams.image')
            ->orderByDesc('total')
            ->limit(5)
            ->get();
        $yellowCards = DB::table('game_events')
            ->join('games','games.id','game_events.game_id')
            ->join('players','players.id','game_events.player_id')
            ->join('teams','teams.id','game_events.team_id')
            ->join('users','users.id','players.user_id')
            ->where('games.tournament_id', $tournament->id)
            ->whereIn('game_events.type',[GameEvent::YELLOW_CARD, GameEvent::YELLOW_CARD])
            ->whereNotNull('game_events.player_id')
            ->select(
                'players.id as player_id',
                'users.name as player_name',
                'teams.name as team_name',
                DB::raw("COALESCE(users.image, CONCAT('https://ui-avatars.com/api/?name=', users.name)) as user_image"),
                DB::raw("COALESCE(teams.image, CONCAT('https://ui-avatars.com/api/?name=', REPLACE(COALESCE(teams.name, ''), ' ', '+'))) as team_image"),
                DB::raw('COUNT(*) as total' )
            )
            ->groupBy(
                'players.id',
                'users.name',
                'teams.name',
                'teams.image'
            )
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        $redCards = DB::table('game_events')
            ->join('games','games.id','game_events.game_id')
            ->join('players','players.id','game_events.player_id')
            ->join('teams','teams.id','game_events.team_id')
            ->join('users','users.id','players.user_id')
            ->where('games.tournament_id', $tournament->id)
            ->whereIn('game_events.type',[GameEvent::RED_CARD])
            ->whereNotNull('game_events.player_id')
            ->select(
                'players.id as player_id',
                'users.name as player_name',
                'teams.name as team_name',
                DB::raw("COALESCE(users.image, CONCAT('https://ui-avatars.com/api/?name=', users.name)) as user_image"),
                DB::raw("COALESCE(teams.image, CONCAT('https://ui-avatars.com/api/?name=', REPLACE(COALESCE(teams.name, ''), ' ', '+'))) as team_image"),
                DB::raw('COUNT(*) as total' )
            )
            ->groupBy('players.id','users.name','teams.name','teams.image')
            ->orderByDesc('total')
            ->limit(5)
            ->get();
        return [
            'goals' => $goals,
            'assistance' => $assistance,
            'red_cards' => $redCards,
            'yellow_cards' => $yellowCards
        ];
    }

}