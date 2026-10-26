<?php

namespace App\Exports;

use App\Models\Company;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

class AttendanceDailyExport implements FromArray, WithEvents, ShouldAutoSize
{
    protected array $rows;
    protected array $headers;
    protected string $startDate;
    protected string $endDate;
    protected ?int $companyId;

    public function __construct(array $rows, array $headers, string $startDate, string $endDate, ?int $companyId = null)
    {
        $this->rows      = $rows;
        $this->headers   = $headers;
        $this->startDate = $startDate;
        $this->endDate   = $endDate;
        $this->companyId = $companyId;
    }

    public function array(): array
    {
        return $this->rows;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet     = $event->sheet->getDelegate();
                $colCount  = count($this->headers);
                $lastCol   = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colCount);
                $rowCount  = count($this->rows);

                // ── 1. Insert 5 blank rows for header block ──────────────────────
                $sheet->insertNewRowBefore(1, 5);

                // ── 2. Logo ───────────────────────────────────────────────────────
                $logoPath = $this->getLogoPath();
                if ($logoPath && file_exists($logoPath)) {
                    $drawing = new Drawing();
                    $drawing->setName('Logo');
                    $drawing->setDescription('Logo Perusahaan');
                    $drawing->setPath($logoPath);
                    $drawing->setHeight(60);
                    $drawing->setCoordinates('A1');
                    $drawing->setOffsetX(8);
                    $drawing->setOffsetY(8);
                    $drawing->setWorksheet($sheet);
                }

                // ── 3. Title & subtitle ───────────────────────────────────────────
                $company = $this->companyId
                    ? Company::find($this->companyId)?->name ?? 'Semua Perusahaan'
                    : 'Semua Perusahaan';

                // Background color for entire header block (rows 1-5) — dark teal so white logo is visible
                $headerBgColor = 'FF1B4F72'; // same deep blue as column headers
                $sheet->getStyle("A1:{$lastCol}5")->applyFromArray([
                    'fill' => [
                        'fillType'   => Fill::FILL_SOLID,
                        'startColor' => ['argb' => $headerBgColor],
                    ],
                ]);

                $sheet->mergeCells("B1:{$lastCol}1");
                $sheet->setCellValue('B1', strtoupper($company));
                $sheet->mergeCells("B2:{$lastCol}2");
                $sheet->setCellValue('B2', 'LAPORAN KEHADIRAN HARIAN');
                $sheet->mergeCells("B3:{$lastCol}3");
                $periodLabel = \Carbon\Carbon::parse($this->startDate)->translatedFormat('d F Y')
                    . ' s/d '
                    . \Carbon\Carbon::parse($this->endDate)->translatedFormat('d F Y');
                $sheet->setCellValue('B3', 'Periode: ' . $periodLabel);

                $sheet->getStyle('B1')->applyFromArray([
                    'font'      => ['bold' => true, 'size' => 15, 'color' => ['argb' => 'FFFFFFFF']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                ]);
                $sheet->getStyle('B2')->applyFromArray([
                    'font'      => ['bold' => true, 'size' => 12, 'color' => ['argb' => 'FF85C1E9']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                ]);
                $sheet->getStyle('B3')->applyFromArray([
                    'font'      => ['italic' => true, 'size' => 10, 'color' => ['argb' => 'FFAED6F1']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                ]);

                // Row 4 = blank spacer
                // Row 5 = printed at (right-aligned, lighter text on dark bg)
                $sheet->mergeCells("A5:{$lastCol}5");
                $printedAt = 'Dicetak: ' . now()->translatedFormat('d F Y H:i') . ' WIB';
                $sheet->setCellValue('A5', $printedAt);
                $sheet->getStyle('A5')->applyFromArray([
                    'font'      => ['size' => 9, 'italic' => true, 'color' => ['argb' => 'FF7FB3D3']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT, 'vertical' => Alignment::VERTICAL_CENTER],
                ]);


                // ── 4. Row-height for header block ────────────────────────────────
                $sheet->getRowDimension(1)->setRowHeight(50);
                $sheet->getRowDimension(2)->setRowHeight(20);
                $sheet->getRowDimension(3)->setRowHeight(18);
                $sheet->getRowDimension(4)->setRowHeight(8);
                $sheet->getRowDimension(5)->setRowHeight(16);

                // ── 5. Column-header row (row 6) ──────────────────────────────────
                $headerRow = 6;
                foreach ($this->headers as $i => $heading) {
                    $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i + 1);
                    $sheet->setCellValue("{$colLetter}{$headerRow}", $heading);
                }
                $headerRange = "A{$headerRow}:{$lastCol}{$headerRow}";
                $sheet->getStyle($headerRange)->applyFromArray([
                    'fill' => [
                        'fillType'   => Fill::FILL_SOLID,
                        'startColor' => ['argb' => 'FF1B4F72'],
                    ],
                    'font' => [
                        'bold'  => true,
                        'color' => ['argb' => 'FFFFFFFF'],
                        'size'  => 10,
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical'   => Alignment::VERTICAL_CENTER,
                        'wrapText'   => true,
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color'       => ['argb' => 'FFFFFFFF'],
                        ],
                    ],
                ]);
                $sheet->getRowDimension($headerRow)->setRowHeight(28);

                // ── 6. Data rows styling ───────────────────────────────────────────
                $statusColIndex = array_search('Status', $this->headers);
                $statusColLetter = $statusColIndex !== false
                    ? \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($statusColIndex + 1)
                    : null;

                for ($r = 0; $r < $rowCount; $r++) {
                    $excelRow = $headerRow + 1 + $r;
                    $isEven   = ($r % 2 === 1);

                    // Alternating row background
                    $bgColor = $isEven ? 'FFD6EAF8' : 'FFFFFFFF';
                    $sheet->getStyle("A{$excelRow}:{$lastCol}{$excelRow}")->applyFromArray([
                        'fill' => [
                            'fillType'   => Fill::FILL_SOLID,
                            'startColor' => ['argb' => $bgColor],
                        ],
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => Border::BORDER_THIN,
                                'color'       => ['argb' => 'FFB0C4DE'],
                            ],
                        ],
                        'alignment' => [
                            'vertical' => Alignment::VERTICAL_CENTER,
                        ],
                    ]);

                    // Status cell color
                    if ($statusColLetter) {
                        $statusVal = $sheet->getCell("{$statusColLetter}{$excelRow}")->getValue();
                        $statusColor = match (strtoupper((string) $statusVal)) {
                            'HADIR'      => ['bg' => 'FFD5F5E3', 'fg' => 'FF1E8449'],
                            'TERLAMBAT'  => ['bg' => 'FFFDEDEC', 'fg' => 'FFC0392B'],
                            'MANGKIR'    => ['bg' => 'FFFDEDEC', 'fg' => 'FFC0392B'],
                            'CUTI'       => ['bg' => 'FFEAF4FB', 'fg' => 'FF2471A3'],
                            'IZIN'       => ['bg' => 'FFEAF4FB', 'fg' => 'FF2471A3'],
                            'SAKIT'      => ['bg' => 'FFFFF3E0', 'fg' => 'FFE67E22'],
                            'LIBUR'      => ['bg' => 'FFF5EEF8', 'fg' => 'FF7D3C98'],
                            'BELUM ABSEN'=> ['bg' => 'FFFFFDE7', 'fg' => 'FFF39C12'],
                            default      => ['bg' => $bgColor,   'fg' => 'FF333333'],
                        };
                        $sheet->getStyle("{$statusColLetter}{$excelRow}")->applyFromArray([
                            'fill' => [
                                'fillType'   => Fill::FILL_SOLID,
                                'startColor' => ['argb' => $statusColor['bg']],
                            ],
                            'font' => [
                                'bold'  => true,
                                'color' => ['argb' => $statusColor['fg']],
                            ],
                            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                        ]);
                    }

                    $sheet->getRowDimension($excelRow)->setRowHeight(18);
                }

                // ── 7. Freeze pane below header ───────────────────────────────────
                $sheet->freezePane('A' . ($headerRow + 1));

                // ── 8. Column widths (override auto-size for key cols) ────────────
                $sheet->getColumnDimension('A')->setWidth(14); // Tanggal
                $sheet->getColumnDimension('B')->setWidth(28); // Karyawan

                // ── 9. Footer total row ───────────────────────────────────────────
                $footerRow = $headerRow + $rowCount + 1;
                $sheet->mergeCells("A{$footerRow}:{$lastCol}{$footerRow}");
                $sheet->setCellValue("A{$footerRow}", "Total Data: {$rowCount} baris");
                $sheet->getStyle("A{$footerRow}")->applyFromArray([
                    'font'      => ['bold' => true, 'size' => 9, 'color' => ['argb' => 'FF888888']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
                    'fill'      => [
                        'fillType'   => Fill::FILL_SOLID,
                        'startColor' => ['argb' => 'FFF2F3F4'],
                    ],
                ]);

                // ── 10. Page setup ────────────────────────────────────────────────
                $sheet->getPageSetup()->setOrientation(
                    \PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE
                );
                $sheet->getPageSetup()->setPaperSize(
                    \PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4
                );
                $sheet->getPageSetup()->setFitToPage(true);
                $sheet->getPageSetup()->setFitToWidth(1);
                $sheet->getPageSetup()->setFitToHeight(0);
                $sheet->getHeaderFooter()
                    ->setOddHeader('&C&B&16Laporan Kehadiran Harian');
                $sheet->getHeaderFooter()
                    ->setOddFooter('&L' . $periodLabel . '&RHalaman &P dari &N');
            },
        ];
    }

    private function getLogoPath(): ?string
    {
        if ($this->companyId) {
            $company = Company::find($this->companyId);
            if ($company && $company->logo) {
                $path = storage_path('app/public/' . $company->logo);
                if (file_exists($path)) return $path;
            }
        }

        // Fallback: pick the first available company logo
        $logos = glob(storage_path('app/public/company-logos/*.{png,jpg,jpeg,gif}'), GLOB_BRACE);
        if (!empty($logos)) {
            return $logos[0];
        }

        return null;
    }
}
