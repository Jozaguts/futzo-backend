<?php

namespace App\Exports;

use App\Models\Tournament;
use Maatwebsite\Excel\Concerns\FromCollection;

class TournamentStatsExport implements FromCollection
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return Tournament::all();
    }
}
