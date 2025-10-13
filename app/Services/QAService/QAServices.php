<?php

namespace App\Services\QAService;

use Carbon\Carbon;
use App\Models\StoreChecklistDuty;
use Illuminate\Support\Facades\Auth;
use App\Helpers\GradeCalculatorHelper;
use App\Models\StoreChecklistResponse;
use App\Helpers\FourWeekCalendarHelper;
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
}
