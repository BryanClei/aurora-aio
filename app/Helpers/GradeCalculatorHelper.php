<?php

namespace App\Helpers;

use App\Models\User;
use App\Models\Checklist;
use App\Models\ScoreRating;

class GradeCalculatorHelper
{
    public static function calculate(
        int $checklistId,
        array $responses,
        bool $isStoreVisit = false
    ): array {
        // Load checklist with sections and questions
        $checklist = Checklist::with(["sections.questions"])->findOrFail(
            $checklistId
        );

        $totalSections = $checklist->sections->count();

        if ($totalSections === 0) {
            return self::emptyGradeResult();
        }

        $pointsPerSection = 100 / $totalSections;

        $sectionBreakdown = [];
        $totalEarnedScore = 0;

        $responsesBySection = collect($responses)->groupBy("section_id");

        foreach ($checklist->sections as $section) {
            $sectionData = self::calculateSectionScore(
                $section,
                $pointsPerSection,
                $responsesBySection->get($section->id, collect([]))
            );

            $totalEarnedScore += $sectionData["earned_points"];
            $sectionBreakdown[] = $sectionData;
        }

        // If store visit is true, set grade to 0
        if ($isStoreVisit) {
            $totalEarnedScore = 0;
        }

        return [
            "grade" => round($totalEarnedScore, 2),
            "total_score" => round($totalEarnedScore, 2),
            "max_score" => 100,
            "percentage" => round($totalEarnedScore, 2),
            "total_sections" => $totalSections,
            "points_per_section" => round($pointsPerSection, 2),
            "breakdown" => $sectionBreakdown,
            "store_visit" => $isStoreVisit,
        ];
    }

    private static function calculateSectionScore(
        $section,
        float $pointsPerSection,
        $sectionResponses
    ): array {
        $totalQuestionsInSection = $section->questions->count();

        if ($totalQuestionsInSection === 0) {
            return [
                "section_id" => $section->id,
                "section_title" => $section->title,
                "section_order_index" => $section->order_index,
                "max_points" => round($pointsPerSection, 2),
                "earned_points" => 0,
                "percentage" => 0,
                "total_questions" => 0,
                "questions" => [],
            ];
        }

        $pointsPerQuestion = $pointsPerSection / $totalQuestionsInSection;

        $sectionScore = 0;
        $questionBreakdown = [];

        foreach ($section->questions as $question) {
            $questionData = self::calculateQuestionScore(
                $question,
                $pointsPerQuestion,
                $sectionResponses->firstWhere("question_id", $question->id)
            );

            $sectionScore += $questionData["earned_points"];
            $questionBreakdown[] = $questionData;
        }

        return [
            "section_id" => $section->id,
            "section_title" => $section->title,
            "section_order_index" => $section->order_index,
            "max_points" => round($pointsPerSection, 2),
            "earned_points" => round($sectionScore, 2),
            "percentage" => round($sectionScore, 2),
            "total_questions" => $totalQuestionsInSection,
            "questions" => $questionBreakdown,
        ];
    }

    private static function calculateQuestionScore(
        $question,
        float $pointsPerQuestion,
        $response
    ): array {
        $hasRemarks = false;
        $pointsEarned = 0;
        $remarks = null;
        $ratingId = null;
        $answerText = null;

        if ($response) {
            $remarks = $response["remarks"] ?? null;
            $hasRemarks = !empty($remarks);

            if (
                isset($response["question_type"]) &&
                $response["question_type"] === "multiple_choice"
            ) {
                $ratingId = $response["answer"] ?? null;
                $answerText = $response["answer_text"] ?? null;

                if ($ratingId !== null) {
                    $scoreRating = ScoreRating::find($ratingId);

                    if ($scoreRating) {
                        $scorePercentage = $scoreRating->score / 100;
                        $pointsEarned = $pointsPerQuestion * $scorePercentage;
                    }
                }
            } elseif (
                isset($response["question_type"]) &&
                $response["question_type"] === "checkboxes"
            ) {
                // Add checkboxes handling
                $answerText = $response["answer_text"] ?? null;

                if (!$hasRemarks) {
                    $pointsEarned = $pointsPerQuestion;
                }
            } elseif (
                isset($response["question_type"]) &&
                $response["question_type"] === "paragraph"
            ) {
                // Add paragraph handling
                $answerText = $response["answer_text"] ?? null;

                if (!$hasRemarks) {
                    $pointsEarned = $pointsPerQuestion;
                }
            } else {
                if (!$hasRemarks) {
                    $pointsEarned = $pointsPerQuestion;
                }
            }
        }

        return [
            "question_id" => $question->id,
            "question_text" => $question->question_text,
            "question_order_index" => $question->order_index,
            "max_points" => round($pointsPerQuestion, 2),
            "earned_points" => round($pointsEarned, 2),
            "has_remarks" => $hasRemarks,
            "remarks" => $remarks,
            "answered" => $response !== null,
            "answer_text" => $answerText, // Add this to the return array
            "rating_id" => $ratingId,
            "question_type" => $response["question_type"] ?? null,
        ];
    }

    private static function emptyGradeResult(): array
    {
        return [
            "grade" => 0,
            "total_score" => 0,
            "max_score" => 100,
            "percentage" => 0,
            "total_sections" => 0,
            "points_per_section" => 0,
            "breakdown" => [],
        ];
    }

    public static function calculateQuick(
        int $checklistId,
        array $responses,
        bool $isStoreVisit = false
    ): float {
        $result = self::calculate($checklistId, $responses, $isStoreVisit);
        return $result["grade"];
    }

    /**
     * Fetch store duty information by IDs
     *
     * @param array $storeDutyIds
     * @return \Illuminate\Support\Collection
     */
    public static function getStoreDuties(array $storeDutyIds)
    {
        return User::whereIn("id", $storeDutyIds)
            ->select("id", "first_name", "last_name")
            ->get()
            ->map(function ($duty) {
                return [
                    "id" => $duty->id,
                    "first_name" => $duty->first_name,
                    "last_name" => $duty->last_name,
                    "full_name" => trim(
                        $duty->first_name . " " . $duty->last_name
                    ),
                ];
            });
    }
}
