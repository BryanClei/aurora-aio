<?php

namespace App\Exports\Reports;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class StoreGradesExport implements
    FromCollection,
    WithHeadings,
    WithStyles,
    WithColumnWidths,
    WithEvents
{
    protected $calendar;
    protected $area;

    public function __construct($calendar, $area)
    {
        $this->calendar = $calendar;
        $this->area = $area;
    }

    public function collection()
    {
        $monthNum = $this->calendar->month;
        $monthName = Carbon::create()
            ->month($monthNum)
            ->format("M");

        $rows = collect([
            [
                $monthName .
                "-" .
                $this->calendar->week .
                "-" .
                $this->calendar->year,
            ],
            ["Passing Score 93.00%"],
            [""],
            [""],
        ]);

        $weekCount = $this->calendar->week ?? 4;
        $storeRows = [];

        // Store weekly grades for area subtotal calculation
        $areaWeekTotals = array_fill(1, $weekCount, []);
        $areaAllAverages = [];

        // Loop through stores under the area
        foreach ($this->area->store as $store) {
            $rowData = [$store->name];
            $weekGrades = array_fill(1, $weekCount, "-");

            foreach ($store->store_checklist as $checklist) {
                foreach ($checklist->weekly_record as $record) {
                    if ($record->week >= 1 && $record->week <= $weekCount) {
                        $weekGrades[$record->week] =
                            number_format((float) $record->weekly_grade, 2) .
                                "%" ??
                            "-";
                    }
                }
            }

            // Compute store average
            $numericGrades = [];
            foreach ($weekGrades as $week => $grade) {
                $rowData[] = $grade;
                if ($grade !== "-") {
                    $value = (float) str_replace("%", "", $grade);
                    $numericGrades[] = $value;
                    $areaWeekTotals[$week][] = $value;
                }
            }

            if (count($numericGrades) > 0) {
                $average = array_sum($numericGrades) / count($numericGrades);
                $rowData[] = number_format($average, 2) . "%";
                $areaAllAverages[] = $average;
            } else {
                $rowData[] = "-";
            }

            $storeRows[] = $rowData;
        }

        // Push store rows
        foreach ($storeRows as $r) {
            $rows->push($r);
        }

        // --- AREA SUBTOTAL ROW ---
        $subtotalRow = ["Sub total {$this->area->name}"];
        $weekAverages = [];

        // Compute per-week averages
        for ($i = 1; $i <= $weekCount; $i++) {
            if (count($areaWeekTotals[$i]) > 0) {
                $weekAvg =
                    array_sum($areaWeekTotals[$i]) / count($areaWeekTotals[$i]);
                $subtotalRow[] = number_format($weekAvg, 2) . "%";
                $weekAverages[] = $weekAvg;
            } else {
                $subtotalRow[] = "-";
            }
        }

        // Compute overall area average
        if (count($areaAllAverages) > 0) {
            $overallAvg = array_sum($areaAllAverages) / count($areaAllAverages);
            $subtotalRow[] = number_format($overallAvg, 2) . "%";
        } else {
            $subtotalRow[] = "-";
        }

        $rows->push($subtotalRow);

        return $rows;
    }

    public function headings(): array
    {
        return ["RDF Feed Livestock Foods Inc"];
    }

    public function styles(Worksheet $sheet)
    {
        foreach (["A1:F1", "A2:F2", "A3:F3", "A4:F4"] as $range) {
            $sheet->mergeCells($range);
            $sheet
                ->getStyle($range)
                ->getFont()
                ->setBold(true);
            $sheet
                ->getStyle($range)
                ->getAlignment()
                ->setHorizontal("center");
        }
        return [];
    }

    public function columnWidths(): array
    {
        $weekCount = $this->calendar->week ?? 4;
        $columns = ["A" => 25];
        $colIndex = 2;

        for ($i = 1; $i <= $weekCount + 1; $i++) {
            $colLetter = Coordinate::stringFromColumnIndex($colIndex);
            $columns[$colLetter] = 15;
            $colIndex++;
        }

        return $columns;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $weekCount = $this->calendar->week ?? 4;

                // --- HEADER ---
                $columnIndex = 2;
                $ordinalLabels = ["1st", "2nd", "3rd", "4th", "5th", "6th"];
                $sheet->setCellValue("A5", "Store");

                for ($i = 1; $i <= $weekCount; $i++) {
                    $colLetter = Coordinate::stringFromColumnIndex(
                        $columnIndex
                    );
                    $sheet->setCellValue(
                        "{$colLetter}5",
                        $ordinalLabels[$i - 1]
                    );
                    $columnIndex++;
                }

                $avgColLetter = Coordinate::stringFromColumnIndex($columnIndex);
                $sheet->setCellValue("{$avgColLetter}5", "Average");

                $lastColumn = Coordinate::stringFromColumnIndex($columnIndex);
                $headerRange = "A5:{$lastColumn}5";

                $sheet
                    ->getStyle($headerRange)
                    ->getFont()
                    ->setBold(true);
                $sheet
                    ->getStyle($headerRange)
                    ->getAlignment()
                    ->setHorizontal("center");
                $sheet->getStyle($headerRange)->applyFromArray([
                    "borders" => [
                        "allBorders" => [
                            "borderStyle" => Border::BORDER_THIN,
                            "color" => ["argb" => "000000"],
                        ],
                    ],
                    "fill" => [
                        "fillType" => Fill::FILL_SOLID,
                        "startColor" => ["argb" => "ffed4f"],
                    ],
                ]);

                // --- DATA STYLING ---
                $lastRow = $sheet->getHighestRow();
                if ($lastRow > 5) {
                    $dataRange = "A6:{$lastColumn}{$lastRow}";
                    $sheet->getStyle($dataRange)->applyFromArray([
                        "borders" => [
                            "allBorders" => [
                                "borderStyle" => Border::BORDER_THIN,
                                "color" => ["argb" => "000000"],
                            ],
                        ],
                    ]);
                    $sheet
                        ->getStyle("B6:{$lastColumn}{$lastRow}")
                        ->getAlignment()
                        ->setHorizontal("center");

                    // --- STYLE AREA SUBTOTAL ROW ---
                    $subtotalRow = $lastRow;
                    $subtotalRange = "A{$subtotalRow}:{$lastColumn}{$subtotalRow}";
                    $sheet->getStyle($subtotalRange)->applyFromArray([
                        "font" => ["bold" => true],
                        "fill" => [
                            "fillType" => Fill::FILL_SOLID,
                            "startColor" => ["argb" => "7fc93e"],
                        ],
                    ]);

                    // --- HIGHLIGHT GRADES BELOW 93.00 ---
                    for ($row = 6; $row <= $lastRow; $row++) {
                        for (
                            $col = 2;
                            $col <=
                            Coordinate::columnIndexFromString($lastColumn);
                            $col++
                        ) {
                            $cell = $sheet->getCellByColumnAndRow($col, $row);
                            $value = str_replace("%", "", $cell->getValue());
                            if (is_numeric($value) && (float) $value < 93.0) {
                                $sheet
                                    ->getStyleByColumnAndRow($col, $row)
                                    ->getFont()
                                    ->getColor()
                                    ->setARGB("FF0000"); // red
                            }
                        }
                    }
                }
            },
        ];
    }
}
// ```

// **What changed:**

// 1. **Removed one empty row** from `collection()` - Now it only has 4 rows before the data starts (rows 1-4), so row 5 becomes the header
// 2. **Data now starts at row 6** - Right after the header row at row 5
// 3. **Store display** - Already displaying `$store->name` which are the stores under the area

// **Expected layout:**
// ```
// Row 1: RDF Feed Livestock Foods Inc (merged A1:F1)
// Row 2: Passing Score 93.00% (merged A2:F2)
// Row 3: (empty, merged A3:F3)
// Row 4: (empty, merged A4:F4)
// Row 5: Store | 1st | 2nd | 3rd | 4th (HEADER)
// Row 6: Store A | 95.5 | 92.0 | - | 88.5 (DATA)
// Row 7: Store B | 91.0 | - | 93.5 | 90.0 (DATA)
// ...
