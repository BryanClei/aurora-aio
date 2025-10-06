<?php

namespace App\Helpers;

use App\Models\Checklist;

class GradeCalculatorHelper
{
    /**
     * Calculate grade based on checklist responses
     * Uses your DB structure: checklists → checklist_sections → checklist_questions
     */
    public static function calculate(int $checklistId, array $responses): array
    {
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

        return [
            "grade" => round($totalEarnedScore, 2),
            "total_score" => round($totalEarnedScore, 2),
            "max_score" => 100,
            "percentage" => round($totalEarnedScore, 2),
            "total_sections" => $totalSections,
            "points_per_section" => round($pointsPerSection, 2),
            "breakdown" => $sectionBreakdown,
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
            "max_points" => round($pointsPerSection, 2),
            "earned_points" => round($sectionScore, 2),
            "percentage" => round(($sectionScore / $pointsPerSection) * 100, 2),
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

        if ($response) {
            $remarks = $response["remarks"] ?? null;
            $hasRemarks = !empty($remarks);

            if (!$hasRemarks) {
                $pointsEarned = $pointsPerQuestion;
            }
        }

        return [
            "question_id" => $question->id,
            "question_text" => $question->question_text,
            "max_points" => round($pointsPerQuestion, 2),
            "earned_points" => round($pointsEarned, 2),
            "has_remarks" => $hasRemarks,
            "answered" => $response !== null,
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
        array $responses
    ): float {
        $result = self::calculate($checklistId, $responses);
        return $result["grade"];
    }
}
