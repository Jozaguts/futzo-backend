<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithDrawings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Exception;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;


class TeamsInstructionsSheet implements FromCollection, WithTitle, WithColumnWidths, WithStyles, WithDrawings
{
    public function collection(): Collection
    {
        return collect([
            ['⚠️ Bienvenido a la hoja de instrucciones.'],
            [''],
            ['La segunda pestaña, "Datos", está disponible para registrar la información de los equipos.'],
            ['Puede completarla directamente o, si prefiere, utilizar un nuevo archivo Excel.'],
            [''],
            ['Recuerde: Es indispensable respetar el formato y el orden de las columnas para garantizar una carga exitosa.'],
            ['', '', '', ''],
            ['Campo', 'Descripción', '¿Obligatorio?', 'Ejemplo'],
            ['Nombre del equipo', 'Nombre completo del equipo', 'Sí', 'Tigres FC'],
            ['Sede', 'Locación registrada'],
            ['Color local primario', 'Color principal del uniforme de local en HEX', 'No', '#FF0000'],
            ['Color local secundario', 'Color secundario del uniforme de local en HEX', 'No', '#00FF00'],
            ['Color visitante primario', 'Color principal del uniforme visitante en HEX', 'No', '#0000FF'],
            ['Color visitante secundario', 'Color secundario del uniforme visitante en HEX', 'No', '#FFFF00'],
            ['Nombre del presidente', 'Nombre completo del presidente', 'No', 'Juan Pérez'],
            ['Teléfono del presidente', 'Teléfono del presidente', 'No', '+52 123 456 7890'],
            ['Correo del presidente', 'Correo del presidente', 'No', 'presidente@tigres.com'],
            ['Nombre del entrenador', 'Nombre completo del entrenador', 'No', 'Carlos Gómez'],
            ['Teléfono del entrenador', 'Teléfono del entrenador', 'No', '+52 987 654 3210'],
            ['Correo del entrenador', 'Correo del entrenador', 'No', 'coach@tigres.com'],
            ['', '', '', ''],
            ['⚠️ NOTA IMPORTANTE: Los colores mostrados son solo referencia visual. Ingrese códigos hexadecimales válidos como "#FF0000". El color de fondo no cambia automáticamente si modifica el texto.'],
            ['', '', '', ''],
            ['Ejemplo'],
            ['', '', '', ''],
            [
                'Nombre del equipo',
                'Sede',
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
            ],
            [
                'Leones Negros',
                'Av. Universidad 1200',
                '#123456',
                '#654321',
                '#ABCDEF',
                '#FEDCBA',
                'Miguel Soto',
                '+52 333 987 6543',
                'miguel.soto@leones.mx',
                'Javier Martínez',
                '+52 333 112 2233',
                'javier.martinez@leones.mx',
            ],
            [
                'Águilas Doradas',
                'Calle Palmas 200',
                '#FFAA00',
                '#AA00FF',
                '#00FFAA',
                '#AAFF00',
                'Laura Gómez',
                '+52 555 999 8888',
                'laura.gomez@aguilas.mx',
                'Andrés Pérez',
                '+52 555 777 6666',
                'andres.perez@aguilas.mx',
            ],
            [
                'Tiburones Rojos',
                'Malecón Costero 500',
                '#CC0000',
                '#0033CC',
                '#66FF66',
                '#FF6666',
                'Roberto Díaz',
                '+52 229 333 1111',
                'roberto.diaz@tiburones.mx',
                'Víctor Cruz',
                '+52 229 777 8888',
                'victor.cruz@tiburones.mx',
            ],
        ]);
    }

    public function title(): string
    {
        return 'Instrucciones';
    }

    public function columnWidths(): array
    {
        return [
            'A' => 30, // Nombre del equipo
            'B' => 40, // Sede
            'C' => 30, // Color local primario
            'D' => 30, // Color local secundario
            'E' => 30, // Color visitante primario
            'F' => 30, // Color visitante secundario
            'G' => 30, // Nombre del presidente
            'H' => 30, // Teléfono del presidente
            'I' => 30, // Correo del presidente
            'J' => 30, // Nombre del entrenador
            'K' => 30, // Teléfono del entrenador
            'L' => 30, // Correo del entrenador
        ];
    }

    public function styles(Worksheet $sheet): void
    {
        // Merge de bienvenida
        $sheet->mergeCells('A1:D5');
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

        // Merge Nota
        $sheet->mergeCells('A22:D23');
        $sheet->getStyle('A22')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'wrapText' => true,
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '9155FD'],
            ],
        ]);

        // Headers campo/descripcion
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
        $sheet->getStyle('A8:D8')->applyFromArray([
            'font' => ['bold' => true],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],

        ]);

        // Bordes para ejemplos
        $sheet->mergeCells('A26:D27');
        $sheet->getStyle('A23:L26')->applyFromArray([
            'font' => ['bold' => true],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
        ]);
        $sheet->getStyle('A28:L28')->applyFromArray([
            'font' => ['bold' => true],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],

        ]);
        $sheet->getStyle('A28:L31')->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ]);
        // Proteger la hoja
        $sheet->getProtection()->setSheet(true);
    }

    public function drawings(): array
    {
        $fullLogoPath = public_path(config('constants.logo_path'));
        if (!file_exists($fullLogoPath)) {
            Log::warning('Logo de Futzo no encontrado: ' . $fullLogoPath);
            return [];
        }

        $drawing = new Drawing();
        $drawing->setName('Logo Futzo');
        $drawing->setDescription('Logo Futzo');
        $drawing->setPath($fullLogoPath);
        $drawing->setCoordinates('E1');
        $drawing->setHeight(100);
        $drawing->setWidth(200);
        $drawing->setOffsetX(10);
        $drawing->setOffsetY(10);

        return [$drawing];
    }

}
