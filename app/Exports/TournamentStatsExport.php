<?php

namespace App\Exports;

use App\Models\Tournament;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\FromView;

class TournamentStatsExport implements  FromView
{
    public function __construct(protected array $stats, protected string $leagueName, protected string $tournamentName, protected mixed $currentRound)
    {

    }
    public function view(): View
{
    return view('components.stats.table', [
        'goals' => $this->stats['goals'],
        'assistance' => $this->stats['assistance'],
        'redCards' => $this->stats['red_cards'],
        'yellowCards' =>$this->stats['yellow_cards'],
        'leagueName' => $this->leagueName,
        'tournamentName' => $this->tournamentName,
        'currentRound' => $this->currentRound['round'],
        'currentDate' => today()->translatedFormat('l d M Y'),
        'showDetails' => true,
    ]);
}

}
