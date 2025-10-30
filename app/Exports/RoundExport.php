<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithDefaultStyles;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Exception;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Style\Style;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class RoundExport implements FromArray, WithColumnFormatting, WithColumnWidths, WithDefaultStyles, WithStyles
{
    // Dejamos un pequeño bloque vacío entre la lista de partidos y el pie informativo.
    private const BLANK_ROWS = 4;

    public function __construct(
        protected array $games,
        protected int $round,
        protected string $leagueName,
        protected string $tournamentName,
        protected ?string $byeTeamName = null
    ) {}

    public function array(): array
    {
        $rows = [];

        $rows[] = ["Jornada {$this->round}", '', ''];

        if ($this->byeTeamName) {
            $rows[] = ["Descansa: {$this->byeTeamName}", '', ''];
        }

        $rows[] = ['LOCAL', '', 'VISITANTE'];

        foreach ($this->games as $match) {
            $rows[] = $match;
        }

        for ($i = 0; $i < self::BLANK_ROWS; $i++) {
            $rows[] = ['', '', ''];
        }

        $rows[] = ["{$this->leagueName} | {$this->tournamentName}", '', ''];

        return $rows;
    }

    public function title(): string
    {
        return 'Datos';
    }
    public function columnFormats(): array
    {
        return [
            'B' => NumberFormat::FORMAT_DATE_TIME3,
        ];
    }
    public function columnWidths(): array
    {
        return [
            'A' => 50,
            'B' => 30,
            'C' => 50,
        ];
    }

    /**
     * @throws Exception
     */
    public function styles(Worksheet $sheet): void
    {
        $sheet->mergeCells('A1:C1');

        $sheet->getStyle('A1:C1')->applyFromArray([
            'font' => ['bold' => true],
        ]);

        $columnHeaderRow = 2;

        if ($this->byeTeamName) {
            $sheet->mergeCells('A2:C2');
            $sheet->getStyle('A2:C2')->applyFromArray([
                'font' => ['italic' => true],
            ]);
            $columnHeaderRow = 3;
        }

        $dataRows = count($this->games);
        $footerRow = $columnHeaderRow + $dataRows + self::BLANK_ROWS + 1;

        $sheet->mergeCells("A{$footerRow}:C{$footerRow}");
        $sheet->getStyle("A{$footerRow}:C{$footerRow}")->applyFromArray([
            'font' => ['bold' => true],
        ]);
    }
    public function defaultStyles(Style $defaultStyle): array
    {
        // Or return the styles array
        return [
            'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_CENTER,
            'vertical' => Alignment::VERTICAL_CENTER,
            'wrapText' => true
            ]
        ];
    }

}
