<?php

namespace App\Services\QAService;

use ZipArchive;
use Carbon\Carbon;

use Illuminate\Support\Str;
use Illuminate\Http\UploadedFile;
use App\Models\StoreChecklistDuty;
use Illuminate\Support\Facades\Auth;
use App\Helpers\GradeCalculatorHelper;
use App\Models\StoreChecklistResponse;
use App\Helpers\FourWeekCalendarHelper;
use Illuminate\Support\Facades\Storage;
use App\Models\StoreChecklistWeeklyRecord;

class QAServices
{
    public static function storeResponse(array $data)
    {
        $gradeData = GradeCalculatorHelper::calculate(
            $data["checklist_id"],
            $data["responses"]
        );

        $today = Carbon::today();
        $fourWeekInfo = FourWeekCalendarHelper::getMonthBasedFourWeek($today);

        $week = $fourWeekInfo["week"];
        $month = $fourWeekInfo["month"];
        $year = $fourWeekInfo["year"];

        $storeDuties = GradeCalculatorHelper::getStoreDuties(
            $data["store_duty_id"]
        );

        if (is_string($gradeData)) {
            $gradeData = json_decode($gradeData, true);
        }

        $record = StoreChecklistWeeklyRecord::create([
            "store_checklist_id" => $data["store_checklist_id"],
            "week" => $week,
            "month" => $month,
            "year" => $year,
            "weekly_grade" => $gradeData["grade"] ?? null,
            "graded_by" => Auth::id(),
        ]);

        $storeDutyRecord = StoreChecklistDuty::create([
            "store_checklist_weekly_record_id" => $record->id,
            "store_checklist_id" => $data["store_checklist_id"],
            "staff_id" => json_encode($storeDuties->pluck("id")->toArray()),
            "staff_name" => json_encode(
                $storeDuties->pluck("full_name")->toArray()
            ),
        ]);

        foreach ($gradeData["breakdown"] as $section) {
            foreach ($section["questions"] as $question) {
                $originalResponse = collect($data["responses"])->firstWhere(
                    "question_id",
                    $question["question_id"]
                );

                if ($originalResponse) {
                    $answerText = null;
                    $selectedOptions = null;

                    if ($originalResponse["question_type"] === "paragraph") {
                        $answerText = $originalResponse["answer"];
                    } elseif (is_array($originalResponse["answer"])) {
                        $selectedOptions = json_encode(
                            $originalResponse["answer"]
                        );
                    } else {
                        $selectedOptions = json_encode([
                            $originalResponse["answer"],
                        ]);
                    }

                    $attachmentPath = null;

                    if (isset($originalResponse["attachment"])) {
                        $file = $originalResponse["attachment"];

                        if ($file instanceof UploadedFile) {
                            $filename = sprintf(
                                "%s_Q%s_%s.%s",
                                $data["code"] ?? "checklist",
                                $question["question_id"],
                                $week . "" . $month . "" . $year,
                                $file->getClientOriginalExtension()
                            );

                            // 🔹 OPTION 1: LOCAL (uses Laravel Storage)
                            // Uncomment when working locally:
                            $attachmentPath = $file->storeAs(
                                "checklist_attachments",
                                $filename,
                                "public"
                            );

                            // 🔹 OPTION 2: PRODUCTION (direct upload to cPanel public_html)
                            // Uncomment when deployed to cPanel:
                            // $file->move(public_path("attachment"), $filename);
                            // $attachmentPath = "attachment/" . $filename;
                        }
                    }

                    StoreChecklistResponse::create([
                        "response_id" => $record->id,
                        "section_id" => $section["section_id"],
                        "section_title" => $section["section_title"],
                        "section_score" => $section["earned_points"],
                        "question_id" => $question["question_id"],
                        "question_text" => $question["question_text"],
                        "answer_text" => $answerText,
                        "selected_options" => $selectedOptions,
                        "store_visit" => $data["store_visit"],
                        "expired" => $data["expired"],
                        "condemned" => $data["condemned"],
                        "store_duty_id" => $storeDutyRecord->id,
                        "good_points" => $data["good_points"],
                        "notes" => $data["notes"],
                        "score" => $question["earned_points"],
                        "remarks" => $question["remarks"],
                        "attachment" => $attachmentPath,
                    ]);
                }
            }
        }

        return [
            "grade_data" => $gradeData,
            "weekly_record" => $record,
            "store_duties" => $storeDuties,
            "week_info" => $fourWeekInfo,
        ];
    }

    public static function downloadAttachment($filenames = [], $zip = false)
    {
        $isLocal = config("app.env") === "local";

        $basePath = $isLocal
            ? storage_path(
                "app" .
                    DIRECTORY_SEPARATOR .
                    "public" .
                    DIRECTORY_SEPARATOR .
                    "checklist_attachments"
            )
            : base_path("public_html/pretestomega/aurora/attachment");

        if (empty($filenames)) {
            return response()->json(
                [
                    "message" => "No filenames provided.",
                ],
                400
            );
        }

        // 🟩 SINGLE FILE — Always direct download
        if (count($filenames) === 1) {
            $filePath = $basePath . DIRECTORY_SEPARATOR . $filenames[0]; // ✅ Use DIRECTORY_SEPARATOR

            if (!file_exists($filePath)) {
                return response()->json(
                    [
                        "message" => "File not found.",
                        "file" => $filePath,
                    ],
                    404
                );
            }

            return response()->download($filePath);
        }

        // 🟩 MULTIPLE FILES — ZIP if requested
        if ($zip === true) {
            $tempDir = storage_path("app" . DIRECTORY_SEPARATOR . "temp");
            if (!file_exists($tempDir)) {
                mkdir($tempDir, 0755, true);
            }

            $zipName = "attachments_" . Str::random(6) . ".zip";
            $zipPath = $tempDir . DIRECTORY_SEPARATOR . $zipName;

            $zipper = new ZipArchive();
            if ($zipper->open($zipPath, ZipArchive::CREATE) === true) {
                $added = false;

                foreach ($filenames as $file) {
                    $filePath = $basePath . DIRECTORY_SEPARATOR . $file; // ✅ Use DIRECTORY_SEPARATOR
                    if (file_exists($filePath)) {
                        $zipper->addFile($filePath, basename($filePath));
                        $added = true;
                    }
                }

                $zipper->close();

                if (!$added) {
                    return response()->json(
                        [
                            "message" =>
                                "No valid files found to include in ZIP.",
                            "base_path" => $basePath,
                        ],
                        404
                    );
                }

                return response()
                    ->download($zipPath)
                    ->deleteFileAfterSend(true);
            }

            return response()->json(
                [
                    "message" => "Failed to create ZIP file.",
                ],
                500
            );
        }

        // 🟨 MULTIPLE FILES, zip = false — return download URLs instead
        $urls = [];
        $notFound = [];
        foreach ($filenames as $file) {
            $filePath = $basePath . DIRECTORY_SEPARATOR . $file; // ✅ Use DIRECTORY_SEPARATOR
            if (file_exists($filePath)) {
                $url = $isLocal
                    ? asset("storage/checklist_attachments/" . $file)
                    : url("pretestomega/aurora/attachment/" . $file);

                $urls[] = $url;
            } else {
                $notFound[] = $filePath;
            }
        }

        return response()->json([
            "message" => "Multiple files available for download.",
            "download_urls" => $urls,
            "not_found" => $notFound,
            "zip_used" => $zip,
            "base_path" => $basePath,
        ]);
    }
}
