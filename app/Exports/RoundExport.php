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
    public function __construct(protected array $games, protected int $round, protected string $leagueName, protected string $tournamentName) {}

    public function array(): array
    {
         return [
             [ "Jornada $this->round",],
             [ 'LOCAL', '', 'VISITANTE'],
             $this->games,
             [''],
             [''],
             [''],
             [''],
             [''],
             ["$this->leagueName | $this->tournamentName" ],
         ];
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
        $headingRows = 1;
        $titlesRows = 1;
        $spreedRows = 5;
        $dataRows = count($this->games);
        $currentRow = 1;
        $startAndEndLeagueRow = $headingRows + $titlesRows + $spreedRows +  $dataRows + $currentRow;
        $sheet->mergeCells("A$startAndEndLeagueRow:C$startAndEndLeagueRow");
        $sheet->getStyle("A$startAndEndLeagueRow:C$startAndEndLeagueRow")->applyFromArray([
            'font' => ['bold' => true],
        ]);
        $sheet->getStyle("A1:C1")->applyFromArray([
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
