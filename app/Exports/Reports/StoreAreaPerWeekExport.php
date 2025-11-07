<?php

namespace App\Exports\Reports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class StoreAreaPerWeekExport implements
    FromCollection,
    WithHeadings,
    WithStyles,
    WithColumnWidths
{
    protected $area;
    protected $week;
    protected $month;
    protected $year;
    protected $checklistGroups = [];

    public function __construct($area, $week, $month, $year)
    {
        $this->area = $area;
        $this->week = $week;
        $this->month = $month;
        $this->year = $year;
    }

    public function collection()
    {
        // Group stores by their checklist
        $checklistGroups = [];

        foreach ($this->area->store as $store) {
            foreach ($store->store_checklist as $checklist) {
                foreach ($checklist->weekly_record as $record) {
                    $auditTrail = $record->audit_trail->first();
                    if ($auditTrail) {
                        $newData = json_decode($auditTrail->new_data, true);
                        $checklistCode =
                            $newData["checklist_snapshot"]["code"] ?? "unknown";
                        $checklistName =
                            $newData["checklist_snapshot"]["name"] ??
                            "Unknown Checklist";

                        if (!isset($checklistGroups[$checklistCode])) {
                            $checklistGroups[$checklistCode] = [
                                "name" => $checklistName,
                                "sections" =>
                                    $newData["checklist_snapshot"][
                                        "sections"
                                    ] ?? [],
                                "stores" => [],
                                "category_columns" => [], // Will be populated in buildChecklistTable
                            ];
                        }

                        $checklistGroups[$checklistCode]["stores"][] = [
                            "store" => $store,
                            "record" => $record,
                            "data" => $newData,
                        ];
                    }
                }
            }
        }

        $this->checklistGroups = $checklistGroups;

        // Build rows - each checklist group separated by empty rows
        $allRows = [];
        $isFirst = true;

        foreach ($checklistGroups as $checklistCode => $group) {
            // Add spacing between tables (except for first table)
            if (!$isFirst) {
                $allRows[] = []; // Empty row
                $allRows[] = []; // Empty row
                $allRows[] = []; // Empty row (3 empty rows for better spacing)
            }
            $isFirst = false;

            // Add checklist name as section header
            $allRows[] = ["Checklist: " . $group["name"]];
            $allRows[] = []; // Empty row for spacing

            // Add table for this checklist
            $tableRows = $this->buildChecklistTable($group);
            $allRows = array_merge($allRows, $tableRows);
        }

        return collect($allRows);
    }

    protected function buildChecklistTable($group)
    {
        $rows = [];

        // Get month name
        $monthName = date("F", mktime(0, 0, 0, $this->month, 1));

        $romanNumerals = [
            "I",
            "II",
            "III",
            "IV",
            "V",
            "VI",
            "VII",
            "VIII",
            "IX",
            "X",
            "XI",
            "XII",
            "XIII",
            "XIV",
            "XV",
            "XVI",
            "XVII",
            "XVIII",
            "XIX",
            "XX",
        ];

        // Build headers and column structure in ORDER
        $headerRow = ["Store", "Findings", "Score"];
        $weekMonthRow = ["{$this->week}st Week {$monthName}", "", ""];
        $columnStructure = [];

        // Track categories we've seen and their section counts
        $categoryCounts = [];
        $processedCategories = [];
        $overallSectionIndex = 0; // For sections without categories
        $categoryColumnIndices = []; // Track which columns are category columns (for styling)

        foreach ($group["sections"] as $index => $section) {
            $categoryName = $section["category_name"] ?? null;
            $sectionTitle = $section["title"] ?? "Section";

            if ($categoryName) {
                // Has category
                // Add category column only if this is the first section in this category
                if (!isset($processedCategories[$categoryName])) {
                    $categoryColumnIndices[] = count($headerRow); // Store column index
                    $headerRow[] = $categoryName;
                    $weekMonthRow[] = "";
                    $columnStructure[] = [
                        "type" => "category",
                        "category" => $categoryName,
                    ];
                    $processedCategories[$categoryName] = true;
                    $categoryCounts[$categoryName] = 0;
                }

                // Add section column with Roman numeral within the category
                $romanIndex = $categoryCounts[$categoryName];
                $headerRow[] =
                    $romanNumerals[$romanIndex] . ". " . $sectionTitle;
                $weekMonthRow[] = "";
                $columnStructure[] = [
                    "type" => "section",
                    "category" => $categoryName,
                    "section_index" => $index,
                ];
                $categoryCounts[$categoryName]++;
            } else {
                // No category - add section with overall sequential Roman numeral
                $headerRow[] =
                    $romanNumerals[$overallSectionIndex] . ". " . $sectionTitle;
                $weekMonthRow[] = "";
                $columnStructure[] = [
                    "type" => "section",
                    "category" => null,
                    "section_index" => $index,
                ];
            }

            $overallSectionIndex++;
        }

        // Store category column indices for styling
        $group["category_columns"] = $categoryColumnIndices;

        $rows[] = $weekMonthRow;
        $rows[] = $headerRow;

        // Store data rows
        foreach ($group["stores"] as $storeData) {
            $row = $this->buildStoreRowForChecklist(
                $storeData,
                $columnStructure
            );
            $rows[] = $row;
        }

        // Totals row
        $storeRows = [];
        foreach ($group["stores"] as $storeData) {
            $storeRows[] = $this->buildStoreRowForChecklist(
                $storeData,
                $columnStructure
            );
        }
        $rows[] = $this->calculateTotalsForChecklist($storeRows);

        return $rows;
    }

    protected function buildStoreRowForChecklist($storeData, $columnStructure)
    {
        $store = $storeData["store"];
        $record = $storeData["record"];
        $data = $storeData["data"];

        $row = [
            $store->name,
            $this->extractFindings($data["checklist_snapshot"]),
            $record->weekly_grade,
        ];

        $gradeSummary = $data["grade_summary"] ?? null;
        $sections = $data["checklist_snapshot"]["sections"] ?? [];
        $totalSections = $gradeSummary["total_sections"] ?? count($sections);
        $pointsPerSection =
            $gradeSummary["points_per_section"] ?? 100 / $totalSections;

        // Calculate section scores with proper weighting
        $sectionScores = [];
        foreach ($sections as $index => $section) {
            $sectionScores[$index] = $this->calculateSectionScoreWeighted(
                $section,
                $pointsPerSection
            );
        }

        // Calculate category scores (sum of all sections in that category)
        $categoryScores = [];
        $categoryMaxPoints = [];

        foreach ($sections as $index => $section) {
            $categoryName = $section["category_name"] ?? null;
            if ($categoryName) {
                if (!isset($categoryScores[$categoryName])) {
                    $categoryScores[$categoryName] = 0;
                    $categoryMaxPoints[$categoryName] = 0;
                }
                $categoryScores[$categoryName] += $sectionScores[$index];
                $categoryMaxPoints[$categoryName] += $pointsPerSection;
            }
        }

        // Convert category scores to percentages (earned / max * 100)
        $categoryPercentages = [];
        foreach ($categoryScores as $categoryName => $earnedPoints) {
            $maxPoints = $categoryMaxPoints[$categoryName];
            if ($maxPoints > 0) {
                $categoryPercentages[$categoryName] =
                    ($earnedPoints / $maxPoints) * 100;
            } else {
                $categoryPercentages[$categoryName] = 0;
            }
        }

        // Build columns based on structure
        foreach ($columnStructure as $colInfo) {
            if ($colInfo["type"] === "category") {
                // Category shows percentage of its allocated points
                $categoryName = $colInfo["category"];
                if (isset($categoryPercentages[$categoryName])) {
                    $row[] =
                        round($categoryPercentages[$categoryName], 2) . "%";
                } else {
                    $row[] = "0%";
                }
            } else {
                // Section column - show as percentage of its allocated points
                $sectionIndex = $colInfo["section_index"];
                $sectionPercent =
                    ($sectionScores[$sectionIndex] / $pointsPerSection) * 100;
                $row[] = round($sectionPercent, 2) . "%";
            }
        }

        return $row;
    }

    protected function calculateSectionScoreWeighted(
        $section,
        $pointsPerSection
    ) {
        $totalScore = 0;
        $maxScore = 0;
        $questionCount = 0;

        // Count only multiple choice questions
        foreach ($section["questions"] as $question) {
            if ($question["question_type"] === "multiple_choice") {
                $questionCount++;
            }
        }

        if ($questionCount === 0) {
            // If no questions, section gets full points
            return $pointsPerSection;
        }

        $pointsPerQuestion = $pointsPerSection / $questionCount;

        // Calculate score based on rating
        foreach ($section["questions"] as $question) {
            if ($question["question_type"] === "multiple_choice") {
                $response = $question["response"] ?? null;
                if ($response && isset($response["selected_option"])) {
                    $score =
                        $response["selected_option"]["score_rating"]["score"] ??
                        0;
                    // Score is out of 100, so we calculate the actual points earned
                    $earnedPoints = ($score / 100) * $pointsPerQuestion;
                    $totalScore += $earnedPoints;
                }
                $maxScore += $pointsPerQuestion;
            }
        }

        return $totalScore;
    }

    protected function calculateTotalsForChecklist($rows)
    {
        $totals = ["Sub Total Area " . $this->area->name, "", 0];

        $count = count($rows);

        if ($count === 0) {
            return $totals;
        }

        // Calculate average score (3rd column, index 2)
        $totalScore = 0;
        foreach ($rows as $row) {
            $totalScore += floatval($row[2]);
        }
        $totals[2] = round($totalScore / $count, 2);

        // Calculate averages for all other columns (category and section columns)
        $columnCount = count($rows[0]);
        for ($colIndex = 3; $colIndex < $columnCount; $colIndex++) {
            $total = 0;
            $validCount = 0;

            foreach ($rows as $row) {
                if (isset($row[$colIndex])) {
                    $value = str_replace("%", "", $row[$colIndex]);
                    if ($value !== "N/A" && is_numeric($value)) {
                        $total += floatval($value);
                        $validCount++;
                    }
                }
            }

            if ($validCount > 0) {
                $totals[] = round($total / $validCount) . "%";
            } else {
                $totals[] = "N/A";
            }
        }

        return $totals;
    }

    protected function calculateSectionScore($section)
    {
        // This is kept for compatibility but not used in main calculation
        $totalScore = 0;
        $maxScore = 0;
        $questionCount = 0;

        foreach ($section["questions"] as $question) {
            if ($question["question_type"] === "multiple_choice") {
                $response = $question["response"] ?? null;
                if ($response && isset($response["selected_option"])) {
                    $score =
                        $response["selected_option"]["score_rating"]["score"] ??
                        0;
                    $totalScore += $score;
                    $maxScore += 100;
                    $questionCount++;
                }
            }
        }

        if ($questionCount === 0) {
            return 100;
        }

        return round(($totalScore / $maxScore) * 100);
    }

    protected function extractFindings($checklistSnapshot)
    {
        $findings = [];
        $sections = $checklistSnapshot["sections"] ?? [];

        foreach ($sections as $section) {
            foreach ($section["questions"] as $question) {
                if ($question["question_type"] === "multiple_choice") {
                    $response = $question["response"] ?? null;
                    if ($response && isset($response["selected_option"])) {
                        $score =
                            $response["selected_option"]["score_rating"][
                                "score"
                            ] ?? 0;
                        if ($score < 100) {
                            $findings[] = $section["title"];
                            break;
                        }
                    }
                }
            }
        }

        return implode(", ", array_unique($findings));
    }

    public function headings(): array
    {
        // Headings are built dynamically in collection() for each table
        return [];
    }

    public function styles(Worksheet $sheet)
    {
        $highestRow = $sheet->getHighestRow();
        $highestColumn = $sheet->getHighestColumn();

        // Track category columns for each table
        $currentTableStartRow = null;
        $currentCategoryColumns = [];

        // Apply styles to all rows
        for ($row = 1; $row <= $highestRow; $row++) {
            $cellValue = $sheet->getCell("A{$row}")->getValue();

            // Check if this is a checklist header row
            if (
                is_string($cellValue) &&
                strpos($cellValue, "Checklist:") === 0
            ) {
                $sheet->mergeCells("A{$row}:{$highestColumn}{$row}");
                $sheet
                    ->getStyle("A{$row}:{$highestColumn}{$row}")
                    ->applyFromArray([
                        "fill" => [
                            "fillType" => Fill::FILL_SOLID,
                            "startColor" => ["rgb" => "D3D3D3"],
                        ],
                        "font" => ["bold" => true, "size" => 12],
                        "alignment" => [
                            "horizontal" => Alignment::HORIZONTAL_LEFT,
                            "vertical" => Alignment::VERTICAL_CENTER,
                        ],
                    ]);
            }
            // Check if this is a week/month header (ends with "Week" followed by month name)
            elseif (
                is_string($cellValue) &&
                preg_match('/^\d+\w+ Week \w+$/', $cellValue)
            ) {
                $currentTableStartRow = $row;

                $sheet->mergeCells("A{$row}:{$highestColumn}{$row}");
                $sheet
                    ->getStyle("A{$row}:{$highestColumn}{$row}")
                    ->applyFromArray([
                        "fill" => [
                            "fillType" => Fill::FILL_SOLID,
                            "startColor" => ["rgb" => "FFFF00"],
                        ],
                        "font" => ["bold" => true, "size" => 14],
                        "alignment" => [
                            "horizontal" => Alignment::HORIZONTAL_CENTER,
                            "vertical" => Alignment::VERTICAL_CENTER,
                        ],
                        "borders" => [
                            "allBorders" => [
                                "borderStyle" => Border::BORDER_THIN,
                            ],
                        ],
                    ]);
            }
            // Check if this is a column header row (starts with "Store")
            elseif ($cellValue === "Store") {
                // Find category columns by checking which headers don't have Roman numerals
                $currentCategoryColumns = [];
                $columnIndex = 0;
                foreach ($sheet->getRowIterator($row, $row) as $rowObj) {
                    foreach ($rowObj->getCellIterator() as $cell) {
                        $value = $cell->getValue();
                        $colLetter = $cell->getColumn();

                        // Category columns don't start with Roman numerals and aren't Store/Findings/Score
                        if (
                            $value &&
                            $value !== "Store" &&
                            $value !== "Findings" &&
                            $value !== "Score" &&
                            !preg_match("/^[IVX]+\./", $value)
                        ) {
                            $currentCategoryColumns[] = $colLetter;
                        }
                    }
                }

                $sheet
                    ->getStyle("A{$row}:{$highestColumn}{$row}")
                    ->applyFromArray([
                        "fill" => [
                            "fillType" => Fill::FILL_SOLID,
                            "startColor" => ["rgb" => "FFFF00"],
                        ],
                        "font" => ["bold" => true],
                        "alignment" => [
                            "horizontal" => Alignment::HORIZONTAL_CENTER,
                            "vertical" => Alignment::VERTICAL_CENTER,
                        ],
                        "borders" => [
                            "allBorders" => [
                                "borderStyle" => Border::BORDER_THIN,
                            ],
                        ],
                    ]);
            }
            // Check if this is a totals row (starts with "Sub Total")
            elseif (
                is_string($cellValue) &&
                strpos($cellValue, "Sub Total") === 0
            ) {
                $sheet
                    ->getStyle("A{$row}:{$highestColumn}{$row}")
                    ->applyFromArray([
                        "fill" => [
                            "fillType" => Fill::FILL_SOLID,
                            "startColor" => ["rgb" => "90EE90"],
                        ],
                        "font" => ["bold" => true],
                        "borders" => [
                            "allBorders" => [
                                "borderStyle" => Border::BORDER_THIN,
                            ],
                        ],
                    ]);

                // Apply orange color to category columns in totals row
                // foreach ($currentCategoryColumns as $colLetter) {
                //     $sheet->getStyle("{$colLetter}{$row}")->applyFromArray([
                //         "fill" => [
                //             "fillType" => Fill::FILL_SOLID,
                //             "startColor" => ["rgb" => "FBD4B4"], // Orange Accent 6, Lighter 60%
                //         ],
                //         "font" => ["bold" => true],
                //         "borders" => [
                //             "allBorders" => [
                //                 "borderStyle" => Border::BORDER_THIN,
                //             ],
                //         ],
                //     ]);
                // }
            }
            // Regular data rows
            elseif (!empty($cellValue) && $cellValue !== "Store") {
                $sheet
                    ->getStyle("A{$row}:{$highestColumn}{$row}")
                    ->applyFromArray([
                        "borders" => [
                            "allBorders" => [
                                "borderStyle" => Border::BORDER_THIN,
                            ],
                        ],
                    ]);

                // Apply orange color to category columns in data rows
                foreach ($currentCategoryColumns as $colLetter) {
                    $sheet->getStyle("{$colLetter}{$row}")->applyFromArray([
                        "fill" => [
                            "fillType" => Fill::FILL_SOLID,
                            "startColor" => ["rgb" => "FBD4B4"], // Orange Accent 6, Lighter 60%
                        ],
                        "borders" => [
                            "allBorders" => [
                                "borderStyle" => Border::BORDER_THIN,
                            ],
                        ],
                    ]);
                }
            }
        }

        return [];
    }

    public function columnWidths(): array
    {
        return [
            "A" => 25,
            "B" => 40,
            "C" => 12,
        ];
    }
}
