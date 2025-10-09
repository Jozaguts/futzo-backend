<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TeamsDataSheet implements FromCollection, WithHeadings, WithTitle, WithColumnWidths, WithStyles
{
    public function collection()
    {
        return collect([
            [
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
            ]
        ]);
    }

    public function headings(): array
    {
        return [
            'Nombre del equipo',
            'Dirección',
            'Color local primario',
            'Color local secundario',
            'Color visitante primario',
            'Color visitante secundario',
            'Nombre del presidente',
            'Teléfono del presidente',
            'Correo del presidente',
            'Nombre del entrenador',
            'Teléfono del entrenador',
            'Correo del entrenador',
        ];
    }

    public function title(): string
    {
        return 'Datos';
    }

    public function columnWidths(): array
    {
        return [
            'A' => 25,
            'B' => 40,
            'C' => 20,
            'D' => 20,
            'E' => 20,
            'F' => 20,
            'G' => 25,
            'H' => 20,
            'I' => 30,
            'J' => 25,
            'K' => 20,
            'L' => 30,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => [
                    'bold' => true,
                ],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                ],
            ],
        ];
    }
}
