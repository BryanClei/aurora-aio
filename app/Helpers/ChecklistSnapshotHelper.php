<?php

namespace App\Helpers;

use App\Models\Checklist;
use App\Models\Store;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class ChecklistSnapshotHelper
{
    /**
     * Create a complete checklist snapshot for audit trail
     *
     * @param array $data - The request payload
     * @param array $gradeData - The calculated grade data
     * @return array
     */
    public static function createSnapshot(
        array $data,
        array $gradeData,
        int $week,
        int $month,
        int $year
    ): array {
        // Load the complete checklist with all relationships
        $checklist = Checklist::with([
            "sections.questions.options",
        ])->findOrFail($data["checklist_id"]);

        $store = Store::with(["areas.region"])->findOrFail($data["store_id"]);
        $inspector = Auth::user();

        // Get store duties
        $storeDuties = isset($data["store_duty_id"])
            ? GradeCalculatorHelper::getStoreDuties($data["store_duty_id"])
            : collect([]);

        // Build the complete snapshot
        $snapshot = [
            "inspection_metadata" => [
                "week" => $week,
                "month" => $month,
                "year" => $year,
                "inspection_date" => now()->toISOString(),
                "inspector" => [
                    "id" => $inspector->id,
                    "full_name" => trim(
                        $inspector->first_name . " " . $inspector->last_name
                    ),
                    "employee_id" => trim(
                        $inspector->id_prefix . " " . $inspector->id_no
                    ),
                ],
                "store" => [
                    "id" => $store->id,
                    "code" => $store->code,
                    "name" => $store->name,
                ],
                "area" => $store->areas
                    ? [
                        "id" => $store->areas->id,
                        "name" => $store->areas->name,
                    ]
                    : null,
                "region" =>
                    $store->areas && $store->areas->region
                        ? [
                            "id" => $store->areas->region->id,
                            "name" => $store->areas->region->name,
                        ]
                        : null,
                "store_duties" => $storeDuties->toArray(),
                "status" => $data["status"] ?? "Completed",
                "store_visit" => $data["store_visit"] ?? 0,
                "expired" => $data["expired"] ?? null,
                "condemned" => $data["condemned"] ?? null,
                "good_points" => $data["good_points"] ?? null,
                "notes" => $data["notes"] ?? null,
            ],
            "checklist_snapshot" => [
                "id" => $checklist->id,
                "code" => $data["code"] ?? $checklist->code,
                "name" => $checklist->name,
                "sections" => self::buildSectionsSnapshot(
                    $checklist,
                    $data["responses"] ?? []
                ),
            ],
            "grade_summary" => [
                "total_grade" => $gradeData["grade"] ?? 0,
                "total_score" => $gradeData["total_score"] ?? 0,
                "max_score" => $gradeData["max_score"] ?? 100,
                "percentage" => $gradeData["percentage"] ?? 0,
                "total_sections" => $gradeData["total_sections"] ?? 0,
                "points_per_section" => $gradeData["points_per_section"] ?? 0,
            ],
        ];

        return $snapshot;
    }

    /**
     * Build sections snapshot with all questions and options
     *
     * @param Checklist $checklist
     * @param array $responses
     * @return array
     */
    private static function buildSectionsSnapshot(
        $checklist,
        array $responses
    ): array {
        $sectionsData = [];
        $responsesByQuestion = collect($responses)->keyBy("question_id");

        foreach ($checklist->sections as $section) {
            $questionsData = [];

            $section_name = null;

            if ($section->category_id != null) {
                $section_name = $section->category->name;
            }

            foreach ($section->questions as $question) {
                $response = $responsesByQuestion->get($question->id);

                $questionData = [
                    "id" => $question->id,
                    "question_type" => $question->question_type,
                    "question_text" => $question->question_text,
                    "order_index" => $question->order_index,
                    "options" => self::buildOptionsSnapshot($question),
                    "response" => self::buildResponseData($question, $response),
                ];

                $questionsData[] = $questionData;
            }

            $sectionsData[] = [
                "id" => $section->id,
                "category_id" => $section->category_id ?? null,
                "category_name" => $section_name,
                "title" => $section->title,
                "description" => $section->description,
                "order_index" => $section->order_index,
                "questions" => $questionsData,
            ];
        }

        return $sectionsData;
    }

    /**
     * Build options snapshot for a question
     *
     * @param $question
     * @return array
     */
    private static function buildOptionsSnapshot($question): array
    {
        $optionsData = [];

        foreach ($question->options as $option) {
            $optionsData[] = [
                "id" => $option->id,
                "option_text" => $option->option_text,
                "order_index" => $option->order_index,
                "score_rating_id" => $option->score_rating_id,
                "score_rating" => $option->scoreRating
                    ? [
                        "id" => $option->scoreRating->id,
                        "rating" => $option->scoreRating->rating,
                        "score" => $option->scoreRating->score,
                    ]
                    : null,
            ];
        }

        return $optionsData;
    }

    /**
     * Build response data for a question
     *
     * @param $question
     * @param $response
     * @return array|null
     */
    private static function buildResponseData($question, $response): ?array
    {
        if (!$response) {
            return null;
        }

        $responseData = [
            "question_type" =>
                $response["question_type"] ?? $question->question_type,
            "answer" => $response["answer"] ?? null,
            "answer_text" => $response["answer_text"] ?? null,
            "remarks" => $response["remarks"] ?? null,
            "attachment" => $response["attachment"] ?? null,
        ];

        // For multiple choice, include the selected option details
        if (
            $question->question_type === "multiple_choice" &&
            isset($response["answer"])
        ) {
            $selectedOption = $question->options->firstWhere(
                "score_rating_id",
                $response["answer"]
            );
            if ($selectedOption) {
                $responseData["selected_option"] = [
                    "id" => $selectedOption->id,
                    "option_text" => $selectedOption->option_text,
                    "score_rating_id" => $selectedOption->score_rating_id,
                    "score_rating" => $selectedOption->scoreRating
                        ? [
                            "id" => $selectedOption->scoreRating->id,
                            "rating" => $selectedOption->scoreRating->rating,
                            "score" => $selectedOption->scoreRating->score,
                        ]
                        : null,
                ];
            }
        }

        // For checkboxes, include selected options details
        if (
            $question->question_type === "checkboxes" &&
            isset($response["answer"])
        ) {
            $answerIds = is_array($response["answer"])
                ? $response["answer"]
                : json_decode($response["answer"], true);

            $selectedOptions = $question->options->whereIn("id", $answerIds);
            $responseData["selected_options"] = $selectedOptions
                ->map(function ($option) {
                    return [
                        "id" => $option->id,
                        "option_text" => $option->option_text,
                        "order_index" => $option->order_index,
                    ];
                })
                ->values()
                ->toArray();
        }

        return $responseData;
    }

    /**
     * Save snapshot to a JSON column
     *
     * @param array $snapshot
     * @return string
     */
    public static function encodeSnapshot(array $snapshot): string
    {
        return json_encode(
            $snapshot,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );
    }

    /**
     * Decode snapshot from JSON
     *
     * @param string $jsonSnapshot
     * @return array
     */
    public static function decodeSnapshot(string $jsonSnapshot): array
    {
        return json_decode($jsonSnapshot, true);
    }
}
