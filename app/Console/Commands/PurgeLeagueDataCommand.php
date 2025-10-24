<?php

namespace App\Console\Commands;

use App\Models\DefaultLineupPlayer;
use App\Models\Field;
use App\Models\Game;
use App\Models\GameActionDetail;
use App\Models\GameEvent;
use App\Models\GameGeneralDetail;
use App\Models\GameTimeDetail;
use App\Models\League;
use App\Models\LeagueField;
use App\Models\Lineup;
use App\Models\LineupPlayer;
use App\Models\Location;
use App\Models\Player;
use App\Models\QrConfiguration;
use App\Models\Standing;
use App\Models\Substitution;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\TournamentConfiguration;
use App\Models\TournamentField;
use App\Models\TournamentGroupConfiguration;
use App\Models\TournamentPhase;
use App\Models\TournamentPhaseRule;
use App\Models\TournamentTiebreaker;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PurgeLeagueDataCommand extends Command
{
    protected $signature = 'league:purge
        {league : ID numérica o nombre exacto de la liga a limpiar}
        {--force : Ejecuta sin pedir confirmación}
        {--dry-run : Muestra el resumen sin eliminar datos}
        {--keep-users : Conserva los usuarios y sólo los desacopla de la liga}';

    protected $description = 'Elimina datos de prueba relacionados con una liga sin necesidad de ejecutar db:fresh.';

    public function handle(): int
    {
        $identifier = trim((string) $this->argument('league'));
        $league = $this->findLeague($identifier);

        if (!$league) {
            $this->error("No se encontró la liga con el identificador '{$identifier}'.");
            return self::FAILURE;
        }

        $this->comment("Liga objetivo: [{$league->id}] {$league->name}");

        $dryRun = (bool) $this->option('dry-run');
        $force = (bool) $this->option('force');

        if (!$dryRun && !$force) {
            $question = sprintf(
                'Esto eliminará torneos, equipos, jugadores, campos, ubicaciones y usuarios fake asociados a "%s". ¿Deseas continuar?',
                $league->name
            );

            if (!$this->confirm($question)) {
                $this->warn('Operación cancelada.');
                return self::SUCCESS;
            }
        }

        $stats = [];

        if ($dryRun) {
            $stats = $this->purgeLeague($league, false);
        } else {
            DB::transaction(function () use ($league, &$stats) {
                $stats = $this->purgeLeague($league, true);
            });
        }

        $this->renderSummary($league, $stats, $dryRun);

        return self::SUCCESS;
    }

    private function findLeague(string $identifier): ?League
    {
        $query = League::withTrashed();

        if (is_numeric($identifier)) {
            return $query->find($identifier);
        }

        return $query
            ->where('name', $identifier)
            ->orderByDesc('updated_at')
            ->first();
    }

    /**
     * Purga todos los datos relacionados con la liga y regresa estadísticas de lo realizado.
     *
     * @return array<string, int>
     */
    private function purgeLeague(League $league, bool $commit): array
    {
        $leagueId = $league->id;

        $tournaments = Tournament::withoutGlobalScopes()
            ->with(['configuration.tiebreakers', 'groupConfiguration'])
            ->where('league_id', $leagueId)
            ->get();

        $teamIds = DB::table('league_team')
            ->where('league_id', $leagueId)
            ->pluck('team_id');

        $leagueFields = LeagueField::where('league_id', $leagueId)->get();
        $fieldIds = $leagueFields->pluck('field_id')->unique();

        $locationIds = DB::table('league_location')
            ->where('league_id', $leagueId)
            ->pluck('location_id')
            ->unique();

        $gamesQuery = Game::withoutGlobalScopes()->withTrashed()->where('league_id', $leagueId);
        $standingsQuery = Standing::where('league_id', $leagueId);
        $qrQuery = QrConfiguration::where('league_id', $leagueId);

        $userQuery = User::withoutGlobalScopes()->where('league_id', $leagueId);
        $demoUserQuery = (clone $userQuery)->where(function ($query) {
            $query->whereNull('email')
                ->orWhere('email', 'like', '%@example.com');
        });

        $stats = [
            'tournaments' => $tournaments->count(),
            'tournament_phases' => TournamentPhase::withTrashed()->whereIn('tournament_id', $tournaments->pluck('id'))->count(),
            'tournament_tiebreakers' => TournamentTiebreaker::withTrashed()
                ->whereIn('tournament_configuration_id', TournamentConfiguration::whereIn('tournament_id', $tournaments->pluck('id'))->pluck('id'))
                ->count(),
            'teams' => $teamIds->count(),
            'players' => Player::withoutGlobalScopes()->withTrashed()->whereIn('team_id', $teamIds)->count(),
            'league_fields' => $leagueFields->count(),
            'fields' => Field::withTrashed()->whereIn('id', $fieldIds)->count(),
            'locations' => Location::withTrashed()->whereIn('id', $locationIds)->count(),
            'games' => (clone $gamesQuery)->count(),
            'standings' => (clone $standingsQuery)->count(),
            'qr_configurations' => (clone $qrQuery)->count(),
            'users_total' => $userQuery->count(),
            'users_demo' => $demoUserQuery->count(),
            'users_real' => max(0, $userQuery->count() - $demoUserQuery->count()),
            'users_deleted' => 0,
            'users_detached' => 0,
            'users_protected' => 0,
            'games_removed' => 0,
            'standings_removed' => 0,
            'qr_configurations_removed' => 0,
        ];

        if (!$commit) {
            return $stats;
        }

        $this->purgeTournaments($tournaments);
        $this->purgeTeams($teamIds);
        $fieldRemovalStats = $this->purgeFieldsAndLocations($leagueId, $leagueFields, $fieldIds, $locationIds);
        $stats['fields_removed'] = $fieldRemovalStats['fields_removed'];
        $stats['locations_removed'] = $fieldRemovalStats['locations_removed'];
        $stats['league_fields_removed'] = $fieldRemovalStats['league_fields_removed'];

        Standing::where('league_id', $leagueId)->delete();
        $stats['standings_removed'] = $stats['standings'];

        $stats['games_removed'] = $this->purgeGames($leagueId);

        QrConfiguration::where('league_id', $leagueId)->delete();
        $stats['qr_configurations_removed'] = $stats['qr_configurations'];

        $userCleanup = $this->cleanupUsers($userQuery->get());
        $stats['users_deleted'] = $userCleanup['deleted'];
        $stats['users_detached'] = $userCleanup['detached'];
        $stats['users_protected'] = $userCleanup['protected'];

        return $stats;
    }

    private function purgeTournaments(Collection $tournaments): void
    {
        $tournaments->each(function (Tournament $tournament): void {
            $tournamentId = $tournament->id;

            // Fases y reglas
            $phaseIds = TournamentPhase::withTrashed()
                ->where('tournament_id', $tournamentId)
                ->pluck('id');

            if ($phaseIds->isNotEmpty()) {
                TournamentPhaseRule::whereIn('tournament_phase_id', $phaseIds)->delete();
                TournamentPhase::withTrashed()->whereIn('id', $phaseIds)->forceDelete();
            }

            // Configuraciones
            $configuration = $tournament->configuration;
            if ($configuration) {
                TournamentTiebreaker::withTrashed()
                    ->where('tournament_configuration_id', $configuration->id)
                    ->forceDelete();
                $configuration->delete();
            }

            $groupConfiguration = $tournament->groupConfiguration;
            if ($groupConfiguration instanceof TournamentGroupConfiguration) {
                $groupConfiguration->delete();
            }

            // Pivots y relaciones
            $tournament->teams()->detach();
            $tournament->locations()->detach();

            TournamentField::withTrashed()
                ->where('tournament_id', $tournamentId)
                ->get()
                ->each(static function (TournamentField $tournamentField): void {
                    $tournamentField->forceDelete();
                });

            // Media
            if (method_exists($tournament, 'clearMediaCollection')) {
                $tournament->clearMediaCollection('tournament');
            }

            $tournament->forceDelete();
        });
    }

    private function purgeTeams(Collection $teamIds): void
    {
        if ($teamIds->isEmpty()) {
            return;
        }

        Team::withoutGlobalScopes()
            ->withTrashed()
            ->whereIn('id', $teamIds)
            ->get()
            ->each(function (Team $team): void {
                // Lineups y variantes
                $defaultLineup = $team->defaultLineup;
                if ($defaultLineup) {
                    DefaultLineupPlayer::where('default_lineup_id', $defaultLineup->id)->delete();
                    $defaultLineup->defaultLineupPlayers()->delete();
                    $defaultLineup->delete();
                }

                $lineup = $team->lineup;
                if ($lineup) {
                    LineupPlayer::where('lineup_id', $lineup->id)->delete();
                    $lineup->lineupPlayers()->delete();
                    $lineup->forceDelete();
                }

                $playerBuilder = Player::withoutGlobalScopes()
                    ->withTrashed()
                    ->where('team_id', $team->id);
                $playerIds = $playerBuilder->pluck('id');

                if ($playerIds->isNotEmpty()) {
                    GameEvent::whereIn('player_id', $playerIds)
                        ->orWhereIn('related_player_id', $playerIds)
                        ->delete();

                    DefaultLineupPlayer::whereIn('player_id', $playerIds)->delete();
                    LineupPlayer::whereIn('player_id', $playerIds)->delete();
                }

                Player::withoutGlobalScopes()
                    ->withTrashed()
                    ->whereIn('id', $playerIds)
                    ->forceDelete();

                $team->categories()->detach();
                $team->leagues()->detach();
                $team->tournaments()->detach();

                if (method_exists($team, 'clearMediaCollection')) {
                    $team->clearMediaCollection('team');
                }

                $team->forceDelete();
            });
    }

    private function purgeGames(int $leagueId): int
    {
        $games = Game::withoutGlobalScopes()
            ->withTrashed()
            ->where('league_id', $leagueId)
            ->get();

        if ($games->isEmpty()) {
            return 0;
        }

        $games->each(function (Game $game): void {
            $gameId = $game->id;

            $lineupIds = Lineup::withTrashed()
                ->where('game_id', $gameId)
                ->pluck('id');

            if ($lineupIds->isNotEmpty()) {
                LineupPlayer::whereIn('lineup_id', $lineupIds)->delete();
                Lineup::withTrashed()
                    ->whereIn('id', $lineupIds)
                    ->forceDelete();
            }

            Substitution::where('game_id', $gameId)->delete();
            GameEvent::where('game_id', $gameId)->delete();

            GameActionDetail::withTrashed()
                ->where('game_id', $gameId)
                ->forceDelete();

            GameGeneralDetail::withTrashed()
                ->where('game_id', $gameId)
                ->forceDelete();

            GameTimeDetail::withTrashed()
                ->where('game_id', $gameId)
                ->forceDelete();

            $game->forceDelete();
        });

        return $games->count();
    }

    /**
     * @param  Collection<int,int>  $fieldIds
     * @param  Collection<int,int>  $locationIds
     * @return array<string,int>
     */
    private function purgeFieldsAndLocations(
        int $leagueId,
        Collection $leagueFields,
        Collection $fieldIds,
        Collection $locationIds
    ): array {
        $fieldsRemoved = 0;
        $locationsRemoved = 0;
        $leagueFieldsRemoved = 0;

        $fieldsUsedElsewhere = LeagueField::whereIn('field_id', $fieldIds)
            ->where('league_id', '!=', $leagueId)
            ->pluck('field_id')
            ->unique();

        $locationsUsedElsewhere = DB::table('league_location')
            ->whereIn('location_id', $locationIds)
            ->where('league_id', '!=', $leagueId)
            ->pluck('location_id')
            ->unique();

        // Borramos ventanas y registros pivot de league_fields
        $leagueFields->each(function (LeagueField $leagueField) use (&$leagueFieldsRemoved): void {
            $leagueField->windows()->delete();
            $leagueField->forceDelete();
            $leagueFieldsRemoved++;
        });

        // Campos solo si no se usan en otra liga ni en juegos activos
        $removableFieldIds = $fieldIds->diff($fieldsUsedElsewhere);

        if ($removableFieldIds->isNotEmpty()) {
            Field::withTrashed()
                ->whereIn('id', $removableFieldIds)
                ->get()
                ->each(function (Field $field) use ($leagueId, &$fieldsRemoved): void {
                    $field->forceDelete();
                    $fieldsRemoved++;
                });
        }

        // Desacoplar ubicaciones de la liga
        DB::table('league_location')
            ->where('league_id', $leagueId)
            ->delete();

        $removableLocationIds = $locationIds->diff($locationsUsedElsewhere);

        if ($removableLocationIds->isNotEmpty()) {
            Location::withTrashed()
                ->whereIn('id', $removableLocationIds)
                ->get()
                ->each(function (Location $location) use (&$locationsRemoved): void {
                    if (method_exists($location, 'syncTags')) {
                        $location->syncTags([]);
                    }
                    $location->forceDelete();
                    $locationsRemoved++;
                });
        }

        return [
            'fields_removed' => $fieldsRemoved,
            'locations_removed' => $locationsRemoved,
            'league_fields_removed' => $leagueFieldsRemoved,
        ];
    }

    /**
     * @param  Collection<int,User>  $users
     * @return array{deleted:int,detached:int}
     */
    private function cleanupUsers(Collection $users): array
    {
        $deleted = 0;
        $detached = 0;
        $protected = 0;
        $keepUsers = (bool) $this->option('keep-users');

        $users->each(function (User $user) use (&$deleted, &$detached, &$protected, $keepUsers): void {
            $protectedRoles = [
                'administrador',
                'super administrador',
                'personal administrativo de liga',
            ];
            if (method_exists($user, 'hasAnyRole') && $user->hasAnyRole($protectedRoles)) {
                $protected++;
                return;
            }

            $isDemo = $this->isDemoUser($user);

            if (!$keepUsers && $isDemo) {
                if (method_exists($user, 'syncRoles')) {
                    $user->syncRoles([]);
                }
                if (method_exists($user, 'syncPermissions')) {
                    $user->syncPermissions([]);
                }
                $user->delete();
                $deleted++;
                return;
            }

            $user->league_id = null;
            $user->saveQuietly();
            $detached++;
        });

        return [
            'deleted' => $deleted,
            'detached' => $detached,
            'protected' => $protected,
        ];
    }

    private function isDemoUser(User $user): bool
    {
        $email = (string) $user->email;

        return $email === ''
            || Str::endsWith(Str::lower($email), '@example.com');
    }

    /**
     * @param  array<string,int>  $stats
     */
    private function renderSummary(League $league, array $stats, bool $dryRun): void
    {
        $title = $dryRun ? 'Resumen (modo simulación)' : 'Resumen de limpieza';
        $this->info($title);

        $summary = [
            "Liga objetivo: [{$league->id}] {$league->name}",
            "Torneos: {$stats['tournaments']}",
            "Fases de torneo: {$stats['tournament_phases']}",
            "Usuarios totales: {$stats['users_total']} (fake detectados: {$stats['users_demo']})",
            "Equipos: {$stats['teams']}",
            "Jugadores: {$stats['players']}",
            "Campos asociados: {$stats['fields']}",
            "Ubicaciones asociadas: {$stats['locations']}",
            "Juegos registrados: {$stats['games']}",
            "Standings: {$stats['standings']}",
            "Configuraciones QR: {$stats['qr_configurations']}",
            "Usuarios protegidos por rol: {$stats['users_protected']}",
        ];

        foreach ($summary as $line) {
            $this->line("  - {$line}");
        }

        if (!$dryRun) {
            $details = [
                "Torneos eliminados: {$stats['tournaments']}",
                "Equipos eliminados: {$stats['teams']}",
                'Usuarios eliminados: ' . ($stats['users_deleted'] ?? 0),
                'Usuarios conservados y desacoplados: ' . ($stats['users_detached'] ?? 0),
                'Usuarios protegidos (sin cambios): ' . ($stats['users_protected'] ?? 0),
                'Campos eliminados: ' . ($stats['fields_removed'] ?? 0),
                'Ubicaciones eliminadas: ' . ($stats['locations_removed'] ?? 0),
                'Relaciones liga/campo eliminadas: ' . ($stats['league_fields_removed'] ?? 0),
                'Juegos eliminados: ' . ($stats['games_removed'] ?? 0),
                'Standings eliminados: ' . ($stats['standings_removed'] ?? 0),
                'Configuraciones QR eliminadas: ' . ($stats['qr_configurations_removed'] ?? 0),
            ];

            $this->comment('Acciones ejecutadas:');
            foreach ($details as $line) {
                $this->line("  - {$line}");
            }
        } else {
            $this->comment('Nota: ejecuta nuevamente sin --dry-run para aplicar los cambios.');
        }
    }
}
