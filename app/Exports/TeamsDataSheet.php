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
                '',
                ''
            ]
        ]);
    }

    public function headings(): array
    {
        return [
            'Nombre del equipo',
            'Correo del equipo',
            'Teléfono del equipo',
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
            'B' => 30,
            'C' => 20,
            'D' => 40,
            'E' => 20,
            'F' => 20,
            'G' => 20,
            'H' => 20,
            'I' => 25,
            'J' => 20,
            'K' => 30,
            'L' => 25,
            'M' => 20,
            'N' => 30,
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
