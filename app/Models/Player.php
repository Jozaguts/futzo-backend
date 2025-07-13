<?php

namespace App\Models;

use Database\Factories\PlayerFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Player extends Model
{
    use HasFactory, SoftDeletes, HasSlug;

    protected static function newFactory(): PlayerFactory
    {
        return PlayerFactory::new();
    }

    protected $fillable = [
        'user_id',
        'team_id',
        'position_id',
        'category_id',
        'birthdate',
        'height',
        'weight',
        'dominant_foot',
        'nationality',
        'medical_notes',
        'number',
        'birthdate'
    ];
    protected $hidden = ['created_at', 'updated_at', 'deleted_at'];
    protected $casts = [
        'birthdate' => 'datetime',
    ];
    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom(function (Player $player) {
                return $player->user->name ?? uniqid('player-', true);
            })
            ->saveSlugsTo('slug');
    }
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function goals(): HasMany
    {
        return $this->hasMany(GoalDetail::class);
    }

    public function image()
    {
        return $this->user ? $this->user->image : null;
    }

    public function games(): BelongsToMany
    {
        return $this->belongsToMany(Game::class, 'game_player')
            ->withPivot('entry_minute', 'exit_minute', 'goals', 'assists')
            ->withTimestamps();
    }

// todo Ejemplo: Registrar que el jugador con ID 1 participó en el juego con ID 2
//$player = Player::find(1);
//$game = Game::find(2);
//
//$game->players()->attach($player->id, [
//'minutes_played' => 90,
//'goals' => 1,
//'assists' => 0,
//]);
// todo Actualizar o agregar la participación del jugador sin eliminar otros registros de participación
//$game->players()->syncWithoutDetaching([
//    $player->id => [
//        'minutes_played' => 45,
//        'goals' => 2,
//        'assists' => 1,
//    ],
//]);
//// todo  En el método calculateStatsForPeriod dentro de DashboardStatsService:
//$activePlayersCurrent = Player::whereHas('games', function ($query) use ($startCurrent, $endCurrent) {
//    $query->whereBetween('games.created_at', [$startCurrent, $endCurrent]);
//})->count();
/// todo Obtener los jugadores que participaron en el partido y agruparlos por equipo
//$playersByTeam = $game->players()
//    ->with('team') // Cargar la relación del equipo de cada jugador
//    ->get()
//    ->groupBy('team.name'); // Agrupar por el nombre del equipo, o cualquier otro atributo único del equipo
//}
//// todo  Registrar jugador inicial (jugó todo el partido)
//$game->players()->syncWithoutDetaching([
//    1 => [
//        'entry_minute' => 0, // Inició el partido
//        'exit_minute' => 90, // Terminó el partido
//    ]
//]);
//
////  todo Registrar sustitución (jugador que entró en el minuto 30 y salió en el 80)
//$game->players()->syncWithoutDetaching([
//    2 => [
//        'entry_minute' => 30,
//        'exit_minute' => 80,
//    ]
//]);
//
//// todo  Registrar un jugador que entró en el minuto 60 y jugó hasta el final
//$game->players()->syncWithoutDetaching([
//    3 => [
//        'entry_minute' => 60,
//        'exit_minute' => 90,
//    ]
//]);
//
//todo Accesor en Player.php para calcular minutos jugados en un partido
//public function getMinutesPlayedAttribute()
//{
//    return $this->pivot->exit_minute - $this->pivot->entry_minute;
//}
// todo Obtener los Jugadores con sus Minutos Jugados por Partido y Agrupados por Equipo
//$playersByTeam = $game->players()
//    ->with('team')
//    ->get()
//    ->groupBy('team.name')
//    ->map(function ($players) {
//        return $players->map(function ($player) {
//            return [
//                'name' => $player->name,
//                'team' => $player->team->name,
//                'minutes_played' => $player->pivot->exit_minute - $player->pivot->entry_minute,
//                // Otros datos si es necesario
//            ];
//        });
//    });
}
