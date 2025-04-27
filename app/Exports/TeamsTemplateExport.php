<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class TeamsTemplateExport implements WithMultipleSheets
{


    public function sheets(): array
    {
        return [
            new TeamsInstructionsSheet(),
            new TeamsDataSheet(),
        ];
    }
}
