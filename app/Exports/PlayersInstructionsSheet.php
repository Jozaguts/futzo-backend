<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PlayersInstructionsSheet implements FromCollection, WithTitle, WithColumnWidths, WithStyles
{
    public function collection(): Collection
    {
        return collect([
            ['⚠️ Bienvenido a la hoja de instrucciones.'],
            [''],
            ['La segunda pestaña, "Datos", está disponible para registrar la información de los jugadores.'],
            ['Puede completarla directamente o, si prefiere, utilizar un nuevo archivo Excel.'],
            [''],
            ['Recuerde: Es indispensable respetar el formato y el orden de las columnas para garantizar una carga exitosa.'],
            ['', '', '', ''],
            ['Campo', 'Descripción', '¿Obligatorio?', 'Ejemplo'],
            ['nombre', 'Nombre del jugador', 'Sí', 'Juan'],
            ['apellido', 'Apellido del jugador', 'Sí', 'Pérez'],
            ['correo', 'Email del jugador', 'Sí', 'juan.perez@correo.com'],
            ['teléfono', 'Teléfono del jugador', 'No', '+52 333 123 4567'],
            ['fecha_nacimiento', 'Fecha de nacimiento (YYYY-MM-DD)', 'Sí', '2001-05-23'],
            ['nacionalidad', 'Nacionalidad', 'Sí', 'Mexicano'],
            ['posicion', 'Posición', 'Sí', 'Delantero'],
            ['numero', 'Número de camiseta', 'No', '9'],
            ['altura', 'Altura en centímetros', 'No', '180'],
            ['peso', 'Peso en kilogramos', 'No', '75'],
            ['pie_dominante', 'Pie dominante', 'No', 'Derecho'],
            ['notas_medicas', 'Notas médicas', 'No', 'Asma leve'],
        ]);
    }

    public function title(): string
    {
        return 'Instrucciones';
    }

    public function columnWidths(): array
    {
        return [
            'A' => 25,
            'B' => 40,
            'C' => 15,
            'D' => 30,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->mergeCells('A1:D1');

        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'FFFACD'],
            ],
        ]);
        $sheet->mergeCells('A3:D4');
        $sheet->getStyle('A3:D4')->applyFromArray([
            'font' => ['bold' => true],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'FFFACD'],
            ],
        ]);
        $sheet->mergeCells('A6:D6');
        $sheet->getStyle('A6:D6')->applyFromArray([
            'font' => ['bold' => true],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'FFFACD'],
            ],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(60);
        $sheet->getStyle('A8:D8')->getFont()->setBold(true);

        return [];
    }
}
