<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
class TournamentStandingExport implements FromView
{
    public function __construct(protected array $standing, protected string $leagueName, protected string $tournamentName, protected mixed $currentRound)
    {

    }
    public function view(): View
    {
        return view('exports.tournament.standing', [
            'standing' => $this->standing,
            'leagueName' => $this->leagueName,
            'tournamentName' => $this->tournamentName,
            'currentRound' => $this->currentRound['round'],
            'currentDate' => today()->translatedFormat('l d M Y'),
        ]);
    }

}
