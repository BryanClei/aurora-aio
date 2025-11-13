<?php

namespace App\Services\QAService;

use ZipArchive;
use Carbon\Carbon;

use App\Models\User;
use App\Models\Region;
use App\Models\AutoSkipped;
use Illuminate\Support\Str;
use App\Helpers\AuditTrailHelper;
use Illuminate\Http\UploadedFile;
use App\Models\StoreChecklistDuty;
use Illuminate\Support\Facades\Auth;
use App\Helpers\GradeCalculatorHelper;
use App\Models\StoreChecklistResponse;
use App\Helpers\FourWeekCalendarHelper;
use Illuminate\Support\Facades\Storage;
use App\Helpers\ChecklistSnapshotHelper;
use App\Models\StoreChecklistWeeklyRecord;

class QAServices
{
    public static function storeResponse(array $data)
    {
        $canSubmit = FourWeekCalendarHelper::canSubmitToday(
            $data["store_checklist_id"],
            Auth::id()
        );

        if (!$canSubmit["can_submit"]) {
            return [
                "success" => false,
                "message" => $canSubmit["reason"],
                "error" => true,
            ];
        }
        // Use the automatically determined next available week
        $week = $canSubmit["next_available_week"];

        $isStoreVisit = ($data["store_visit"] ?? 0) == 1;

        $today = Carbon::today();
        $fourWeekInfo = FourWeekCalendarHelper::getMonthBasedFourWeek($today);
        // $week = 3;
        $month = $fourWeekInfo["month"];
        $year = $fourWeekInfo["year"];

        // 🔥 STEP 1: Process all attachments FIRST
        $data["responses"] = self::processResponseAttachments(
            $data["responses"] ?? [],
            $data["code"] ?? "checklist",
            $week,
            $month,
            $year
        );

        // STEP 2: Calculate grade with processed responses
        $gradeData = GradeCalculatorHelper::calculate(
            $data["checklist_id"],
            $data["responses"],
            $isStoreVisit
        );

        // 🔥 STEP 3: CREATE COMPLETE AUDIT TRAIL SNAPSHOT
        $auditSnapshot = ChecklistSnapshotHelper::createSnapshot(
            $data,
            $gradeData,
            $week,
            $month,
            $year
        );

        // Check if this qualifies for auto-grade next week
        $autoGradeApplied = false;
        $finalGrade = $gradeData["grade"] ?? 0;
        $autoCreatedRecord = null;

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
            "weekly_grade" => $finalGrade,
            "graded_by" => Auth::id(),
            "status" => "Completed",
            "store_visit" =>
                $data["store_visit"] == 0 ? null : $data["store_visit"],
            "expired" => $data["expired"],
            "condemned" => $data["condemned"],
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

                    // Attachment is already processed - just extract the path
                    $attachmentPath =
                        $originalResponse["attachment"]["file_path"] ?? null;

                    StoreChecklistResponse::create([
                        "response_id" => $record->id,
                        "weekly_record_id" => $record->id,
                        "section_id" => $section["section_id"],
                        "section_title" => $section["section_title"],
                        "section_score" => $section["earned_points"],
                        "section_order_index" =>
                            $section["section_order_index"],
                        "question_id" => $question["question_id"],
                        "question_type" => $question["question_type"],
                        "question_text" => $question["question_text"],
                        "question_order_index" =>
                            $question["question_order_index"],
                        "answer_text" => $answerText,
                        "selected_options" => $selectedOptions,
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

        // After saving current week, check if we should auto-create next week with 100%
        if (
            self::shouldAutoCreateNextWeek(
                $data["store_checklist_id"],
                $week,
                $month,
                $year,
                $finalGrade,
                $data
            )
        ) {
            $autoCreatedRecord = self::createAutoGradedWeek(
                $data["store_checklist_id"],
                $week + 1,
                $month,
                $year,
                $storeDuties
            );
            $autoGradeApplied = true;
        }

        // 🔥 LOG AUDIT TRAIL - RIGHT BEFORE RETURN
        AuditTrailHelper::activityLogs(
            moduleType: "QA Dashboard",
            moduleName: "Weekly Record",
            moduleId: $record->id,
            action: "Submit",
            newData: $auditSnapshot,
            previousData: null,
            remarks: sprintf(
                "Store: %s (ID: %s) | Week %s, Month %s, Year %s | Grade: %s | Store Visit: %s",
                $auditSnapshot["inspection_metadata"]["store"]["name"] ??
                    "Unknown",
                $data["store_id"],
                $week,
                $month,
                $year,
                $finalGrade,
                $isStoreVisit ? "Yes" : "No"
            )
        );

        return [
            "grade_data" => $gradeData,
            "weekly_record" => $record,
            "store_duties" => $storeDuties,
            "week_info" => $fourWeekInfo,
            "auto_grade_applied" => $autoGradeApplied,
            "auto_created_record" => $autoCreatedRecord,
            "final_grade" => $finalGrade,
            "success" => true,
        ];
    }

    private static function processResponseAttachments(
        array $responses,
        string $code,
        int $week,
        int $month,
        int $year
    ): array {
        $processedResponses = [];

        foreach ($responses as $response) {
            // Check if attachment exists and is an UploadedFile
            if (
                isset($response["attachment"]) &&
                $response["attachment"] instanceof UploadedFile
            ) {
                try {
                    $file = $response["attachment"];

                    $filename = sprintf(
                        "%s_Q%s_%s.%s",
                        $code,
                        $response["question_id"],
                        $week . $month . $year,
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

                    // Replace UploadedFile with structured file info
                    $response["attachment"] = [
                        "file_name" => $filename,
                        "file_path" => $attachmentPath,
                        "file_url" => asset("storage/" . $attachmentPath),
                        "original_name" => $file->getClientOriginalName(),
                        "mime_type" => $file->getMimeType(),
                        "size" => $file->getSize(),
                    ];
                } catch (\Exception $e) {
                    // Log error and set attachment to null
                    \Log::error(
                        "File upload failed for question {$response["question_id"]}: " .
                            $e->getMessage()
                    );
                    $response["attachment"] = null;
                }
            } elseif (
                isset($response["attachment"]) &&
                empty($response["attachment"])
            ) {
                // Clean up empty attachments
                $response["attachment"] = null;
            }

            $processedResponses[] = $response;
        }

        return $processedResponses;
    }

    private static function shouldAutoCreateNextWeek(
        int $storeChecklistId,
        int $currentWeek,
        int $month,
        int $year,
        float $currentGrade,
        array $currentData
    ): bool {
        // Always 4 weeks per month in new system
        $totalWeeks = 4;

        // Only apply when submitting week 3 (second-to-last week)
        if ($currentWeek !== 3) {
            return false;
        }

        // Current submission's grade must be >= 94
        if ($currentGrade < 94) {
            return false;
        }

        // Current submission must not have store_visit, expired, or condemned
        if (
            ($currentData["store_visit"] ?? 0) == 1 ||
            !empty($currentData["expired"]) ||
            !empty($currentData["condemned"])
        ) {
            return false;
        }

        // Check if week 4 already exists
        $nextWeekExists = StoreChecklistWeeklyRecord::where(
            "store_checklist_id",
            $storeChecklistId
        )
            ->where("week", 4)
            ->where("month", $month)
            ->where("year", $year)
            ->exists();

        if ($nextWeekExists) {
            return false;
        }

        // Check all previous weeks (week 1 and week 2)
        if ($currentWeek > 1) {
            $previousWeekNumbers = range(1, $currentWeek - 1);

            $previousWeeks = StoreChecklistWeeklyRecord::where(
                "store_checklist_id",
                $storeChecklistId
            )
                ->where("month", $month)
                ->where("year", $year)
                ->whereIn("week", $previousWeekNumbers)
                ->orderBy("week", "asc")
                ->get();

            // Must have all previous weeks submitted
            if ($previousWeeks->count() !== count($previousWeekNumbers)) {
                return false;
            }

            // Check each previous week
            foreach ($previousWeeks as $weekRecord) {
                // Check if grade is less than 94
                if ($weekRecord->weekly_grade < 94) {
                    return false;
                }

                // Check if any week has store_visit, expired, or condemned
                if (
                    $weekRecord->store_visit == 1 ||
                    !empty($weekRecord->expired) ||
                    !empty($weekRecord->condemned)
                ) {
                    return false;
                }
            }
        }

        // All conditions met: auto-create week 4
        return true;
    }

    private static function createAutoGradedWeek(
        int $storeChecklistId,
        int $week,
        int $month,
        int $year,
        $storeDuties
    ): StoreChecklistWeeklyRecord {
        // Ensure week is valid (1-4)
        if ($week < 1 || $week > 4) {
            throw new \Exception(
                "Invalid week number. Must be between 1 and 4."
            );
        }

        // Create the weekly record with 100% grade
        $record = StoreChecklistWeeklyRecord::create([
            "store_checklist_id" => $storeChecklistId,
            "week" => $week,
            "month" => $month,
            "year" => $year,
            "weekly_grade" => 100,
            "is_auto_grade" => true,
            "grade_source" => "auto",
            "graded_by" => Auth::id(),
            "status" => "Completed",
            "store_visit" => null,
            "expired" => null,
            "condemned" => null,
        ]);

        // Create store duty record
        StoreChecklistDuty::create([
            "store_checklist_weekly_record_id" => $record->id,
            "store_checklist_id" => $storeChecklistId,
            "staff_id" => json_encode($storeDuties->pluck("id")->toArray()),
            "staff_name" => json_encode(
                $storeDuties->pluck("full_name")->toArray()
            ),
        ]);

        return $record;
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
            $filePath = $basePath . DIRECTORY_SEPARATOR . $filenames[0];

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
                    $filePath = $basePath . DIRECTORY_SEPARATOR . $file;
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
            $filePath = $basePath . DIRECTORY_SEPARATOR . $file;
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

    public static function autoSkip(array $data)
    {
        $today = Carbon::today();
        $fourWeekInfo = FourWeekCalendarHelper::getMonthBasedFourWeek($today);
        $region_id = $data["region_id"] ?? null;

        $week = $fourWeekInfo["week"];
        $month = $fourWeekInfo["month"];
        $year = $fourWeekInfo["year"];

        // Validate week is between 1-4
        if ($week < 1 || $week > 4) {
            return "Invalid week number for current date.";
        }

        // Check if within submission period
        if (!$fourWeekInfo["is_within_submission_period"]) {
            return "Cannot skip - submission period has ended (4 days before month end).";
        }

        $region = Region::find($region_id);

        if (!$region || !$region->region_head_id) {
            return "No region head set to this region yet";
        }

        $approver = User::find($region->region_head_id);

        if (!$approver) {
            return "No region approver found! Please contact support.";
        }

        // Check if already exists for this week
        $existingRecord = StoreChecklistWeeklyRecord::where(
            "store_checklist_id",
            $data["store_checklist_id"]
        )
            ->where("week", $week)
            ->where("month", $month)
            ->where("year", $year)
            ->first();

        if ($existingRecord) {
            return "A record already exists for Week {$week} of this month.";
        }

        $record = StoreChecklistWeeklyRecord::create([
            "store_checklist_id" => $data["store_checklist_id"],
            "week" => $week,
            "month" => $month,
            "year" => $year,
            "weekly_grade" => 0,
            "is_auto_grade" => true,
            "grade_source" => "auto",
            "graded_by" => Auth::id(),
            "status" => "Overdue",
        ]);

        $skipped_record = AutoSkipped::create([
            "weekly_id" => $record->id,
            "week" => $week,
            "month" => $month,
            "year" => $year,
            "approver_id" => $approver->id,
            "approver_name" =>
                $approver->first_name . " " . $approver->last_name,
        ]);

        return [
            "weekly_record" => $record,
            "auto_skipped" => $skipped_record,
            "week_info" => $fourWeekInfo,
        ];
    }

    public static function updateResponse($id, array $data)
    {
        $weeklyRecord = StoreChecklistWeeklyRecord::with(
            "weekly_response"
        )->findOrFail($id);

        if (!$weeklyRecord) {
            return __("messages.id_not_found");
        }

        $isStoreVisit = ($data["store_visit"] ?? 0) == 1;

        $week = $weeklyRecord->week;
        $month = $weeklyRecord->month;
        $year = $weeklyRecord->year;

        $data["responses"] = self::processResponseAttachments(
            $data["responses"] ?? [],
            $data["code"] ?? "checklist",
            $week,
            $month,
            $year
        );

        $gradeData = GradeCalculatorHelper::calculate(
            $data["checklist_id"],
            $data["responses"],
            $isStoreVisit
        );

        if (is_string($gradeData)) {
            $gradeData = json_decode($gradeData, true);
        }

        $auditSnapshot = ChecklistSnapshotHelper::createSnapshot(
            $data,
            $gradeData,
            $week,
            $month,
            $year
        );

        $previousSnapshot = $weeklyRecord->snapshot
            ? ChecklistSnapshotHelper::decodeSnapshot($weeklyRecord->snapshot)
            : null;

        $finalGrade = $gradeData["grade"] ?? 0;

        $weeklyRecord->update([
            "weekly_grade" => $finalGrade,
            "graded_by" => Auth::id(),
            "store_visit" =>
                $data["store_visit"] == 0 ? null : $data["store_visit"],
            "expired" => $data["expired"] ?? $weeklyRecord->expired,
            "condemned" => $data["condemned"] ?? $weeklyRecord->condemned,
            "status" => "Completed",
        ]);

        // STEP 5: Get store duties
        $storeDuties = GradeCalculatorHelper::getStoreDuties(
            $data["store_duty_id"]
        );

        $storeDutyRecord = StoreChecklistDuty::create([
            "store_checklist_weekly_record_id" => $weeklyRecord->id,
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

                    // Attachment is already processed - just extract the path
                    $attachmentPath =
                        $originalResponse["attachment"]["file_path"] ?? null;

                    StoreChecklistResponse::create([
                        "response_id" => $weeklyRecord->id,
                        "weekly_record_id" => $weeklyRecord->id,
                        "section_id" => $section["section_id"],
                        "section_title" => $section["section_title"],
                        "section_score" => $section["earned_points"],
                        "section_order_index" =>
                            $section["section_order_index"],
                        "question_id" => $question["question_id"],
                        "question_type" => $question["question_type"],
                        "question_text" => $question["question_text"],
                        "question_order_index" =>
                            $question["question_order_index"],
                        "answer_text" => $answerText,
                        "selected_options" => $selectedOptions,
                        "store_duty_id" => $storeDutyRecord->id,
                        "good_points" => $data["good_points"] ?? null,
                        "notes" => $data["notes"] ?? null,
                        "score" => $question["earned_points"],
                        "remarks" => $question["remarks"],
                        "attachment" => $attachmentPath,
                    ]);
                }
            }
        }

        // 🔥 LOG AUDIT TRAIL - Same as storeResponse
        AuditTrailHelper::activityLogs(
            moduleType: "QA Dashboard",
            moduleName: "Weekly Record",
            moduleId: $weeklyRecord->id,
            action: "Update",
            newData: $auditSnapshot,
            previousData: $previousSnapshot,
            remarks: sprintf(
                "Updated Overdue Record | Store: %s (ID: %s) | Week %s, Month %s, Year %s | Grade: %s → %s | Store Visit: %s",
                $auditSnapshot["inspection_metadata"]["store"]["name"] ??
                    "Unknown",
                $data["store_id"] ?? $weeklyRecord->store_checklist->store_id,
                $week,
                $month,
                $year,
                $previousSnapshot["grade_summary"]["total_grade"] ?? "N/A",
                $finalGrade,
                $isStoreVisit ? "Yes" : "No"
            )
        );

        // Return data in the same format as storeResponse
        return [
            "grade_data" => $gradeData,
            "weekly_record" => $weeklyRecord->fresh(),
            "store_duties" => $storeDuties,
            "final_grade" => $finalGrade,
            "updated" => true,
        ];
    }

    public function forApproval($id, $reason)
    {
        $weekly_record = StoreChecklistWeeklyRecord::find($id);

        if (!$weekly_record) {
            return __("messages.id_not_found");
        }

        if ($weekly_record->status !== "Overdue") {
            return __("messages.overdue_only");
        }

        $weekly_record->update([
            "status" => "For Approval",
            "for_approval_reason" => $reason,
        ]);

        return $weekly_record;
    }
}
