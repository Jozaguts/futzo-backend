<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PlayersDataSheet implements FromCollection, WithTitle, WithColumnWidths, WithStyles
{
    public function collection(): Collection
    {
        return collect([
            [
                'nombre',
                'apellido',
                'correo',
                'teléfono',
                'fecha_nacimiento',
                'nacionalidad',
                'equipo',
                'categoria',
                'posicion',
                'numero',
                'altura',
                'peso',
                'pie_dominante',
                'notas_medicas'
            ],
        ]);
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
            'M' => 15,
            'N' => 30,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->getStyle('A1:N1')->applyFromArray([
            'font' => ['bold' => true],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ],
        ]);

        return [];
    }
}
