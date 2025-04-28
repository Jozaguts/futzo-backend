<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class PlayersTemplateExport implements WithMultipleSheets
{
    public function sheets(): array
    {
        return [
            new PlayersInstructionsSheet(),
            new PlayersDataSheet(),
        ];
    }
}
