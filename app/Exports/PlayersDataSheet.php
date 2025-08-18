<?php

namespace App\Exports;

use App\Models\Position;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\DefaultValueBinder;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Calculation\DateTimeExcel\Date;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Exception;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PlayersDataSheet extends DefaultValueBinder  implements WithCustomValueBinder, FromCollection, WithTitle, WithColumnWidths, WithStyles, WithEvents
{
    public function collection(): Collection
    {
        $positions = Position::select('name')->get()->pluck('name')->toArray();
        $nationalities = config('constants.nationalities');
        $rows = [[
            'nombre',
            'apellido',
            'correo',
            'teléfono',
            'fecha_nacimiento',
            'nacionalidad',
            'posición',
            'numero',
            'altura',
            'peso',
            'pie_dominante',
            'notas_medicas'
        ]];
        // 5 filas vacías para que el usuario llene datos
        for ($i = 0; $i < 5; $i++) {
            $rows[] = [
                '', // nombre
                '', // apellido
                '', // correo
                '', // teléfono
                '', // fecha_nacimiento
                $nationalities, // nacionalidad
                $positions, // 👈 aquí metemos el array, esto activa el dropdown
                '', // numero
                '', // altura
                '', // peso
                '', // pie_dominante
                '', // notas_medicas
            ];
        }
        return collect($rows);

    }

    public function columnFormats(): array
    {
        return [
            'E' => NumberFormat::FORMAT_DATE_DDMMYYYY,
        ];
    }
    public function title(): string
    {
        return 'Datos';
    }

    public function columnWidths(): array
    {
        return [
            'A' => 20,
            'B' => 20,
            'C' => 30,
            'D' => 20,
            'E' => 20,
            'F' => 20,
            'G' => 25,
            'H' => 20,
            'I' => 20,
            'J' => 10,
            'K' => 10,
            'L' => 10,
        ];
    }

    /**
     * @throws Exception
     */
    public function styles(Worksheet $sheet): array
    {
        $sheet->getStyle('A1:N1')->applyFromArray([
            'font' => ['bold' => true],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);
        $sheet->getComment('E1')->getText()->createTextRun(
            'Formato: dd/mm/yyyy. Debe ser una fecha válida.'
        );
        return [];
    }

    /**
     * @throws Exception
     */
    public function bindValue(Cell $cell, $value): bool
    {
        if (is_array($value)) {
            $validation = $cell->getDataValidation();
            $validation->setType(DataValidation::TYPE_LIST);
            $validation->setAllowBlank(true);
            $validation->setShowDropDown(true);
            $validation->setFormula1('"'.collect($value)->join(',').'"');

            $value = '';
        }
        return parent::bindValue($cell, $value);
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // --- Date validation for E column (fecha_nacimiento) ---
                // Use Excel serial numbers to avoid locale/function-name issues.
                // min: 1900-01-01, max: today
                $minExcel = (string) Date::fromYMD('1900','01','01');
                $now  = now();
                $maxExcel = (string) Date::fromYMD($now->year,$now->month,$now->day);

                // Build a reusable validation object
                $dv = new DataValidation();
                $dv->setType(DataValidation::TYPE_DATE);
                $dv->setErrorStyle(DataValidation::STYLE_STOP);
                $dv->setOperator(DataValidation::OPERATOR_BETWEEN);
                $dv->setAllowBlank(true);
                $dv->setShowErrorMessage(true);
                // Disable the unreadable yellow input message
                $dv->setShowInputMessage(false);
                $dv->setErrorTitle('Fecha inválida');
                $dv->setError('Ingresa una fecha válida en formato dd/mm/yyyy.');
                $dv->setFormula1($minExcel);
                $dv->setFormula2($maxExcel);

                // Decide how many rows you want to validate (header is row 1)
                $maxRows = 100; // adjust as needed (or compute dynamically)05/
                for ($row = 2; $row <= $maxRows; $row++) {
                    // Clone the validation per cell
                    $sheet->getCell("E{$row}")->setDataValidation(clone $dv);
                }
            },
        ];
    }
}
