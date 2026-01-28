<?php

namespace App\Services\ChecklistServices;

use App\Models\Category;
use App\Models\Checklist;

class ChecklistService
{
    public function createChecklist(array $data): array
    {
        $new_checklist = Checklist::create([
            "name" => $data["name"],
        ]);

        foreach ($data["sections"] as $sectionData) {
            $categoryId = null;

            if (!empty($sectionData["category"])) {
                $category = Category::firstOrCreate([
                    "name" => $sectionData["category"],
                ]);
                $categoryId = $category->id;
            }

            $section = $new_checklist->sections()->create([
                "title" => $sectionData["title"],
                "checklist_id" => $new_checklist->id,
                "order_index" => $sectionData["order_index"],
                "category_id" => $categoryId,
            ]);

            //section id 1

            foreach ($sectionData["questions"] as $questionData) {
                $question = $section->questions()->create([
                    "section_id" => $section->id, //section id 1
                    "question_text" => $questionData["question_text"],
                    "question_type" => $questionData["question_type"],
                    "order_index" => $questionData["order_index"],
                ]);

                //question id 1

                if (
                    in_array($questionData["question_type"], [
                        "multiple_choice",
                        "checkboxes",
                    ]) &&
                    isset($questionData["options"])
                ) {
                    foreach ($questionData["options"] as $optionData) {
                        $optionPayload = [
                            "question_id" => $question->id,
                            "option_text" => $optionData["option_text"],
                            "order_index" => $optionData["order_index"],
                        ];

                        // Add score_rating_id only for multiple_choice questions
                        if (
                            $questionData["question_type"] === "multiple_choice"
                        ) {
                            $scoreRating =
                                $optionData["score_rating"] ??
                                $optionData["order_index"];
                            $optionPayload["score_rating_id"] = $scoreRating;
                        }

                        $question->options()->create($optionPayload);
                    }
                }
            }
        }

        $new_checklist
            ->fresh()
            ->load(["sections.questions.options.scoreRating"]);

        return ["checklist" => $new_checklist];
    }

    public function updateChecklist(int $checklistId, array $data): array
    {
        $checklist = Checklist::with("sections.questions.options")->find(
            $checklistId
        );

        if (!$checklist) {
            return [
                "checklist" => __("messages.id_not_found"),
                "has_changes" => false,
            ];
        }

        $existingData = $this->normalizeChecklist($checklist);
        $incomingData = $this->normalizeIncoming($data);

        $hasChanges = json_encode($existingData) !== json_encode($incomingData);

        if (!$hasChanges) {
            return [
                "checklist" => $checklist,
                "has_changes" => false,
            ];
        }

        $checklist->update(["name" => $data["name"]]);

        $sectionIds = [];

        foreach ($data["sections"] as $sectionData) {
            // Handle category creation or lookup
            $categoryId = null;
            if (!empty($sectionData["category"])) {
                $category = Category::firstOrCreate([
                    "name" => $sectionData["category"],
                ]);
                $categoryId = $category->id;
            }

            // Update or create section
            $section = $checklist->sections()->updateOrCreate(
                ["id" => $sectionData["id"] ?? null],
                [
                    "title" => $sectionData["title"],
                    "order_index" => (int) $sectionData["order_index"],
                    "category_id" => $categoryId,
                ]
            );

            $sectionIds[] = $section->id;

            // === Questions ===
            $questionIds = [];
            foreach ($sectionData["questions"] as $questionData) {
                $question = $section->questions()->updateOrCreate(
                    ["id" => $questionData["id"] ?? null],
                    [
                        "question_text" => $questionData["question_text"],
                        "question_type" => $questionData["question_type"],
                        "order_index" => (int) $questionData["order_index"],
                    ]
                );

                $questionIds[] = $question->id;

                // === Options ===
                $optionIds = [];
                foreach ($questionData["options"] ?? [] as $optionData) {
                    $optionPayload = [
                        "option_text" => $optionData["option_text"],
                        "order_index" => (int) $optionData["order_index"],
                    ];

                    if ($questionData["question_type"] === "multiple_choice") {
                        $optionPayload["score_rating"] =
                            $optionData["score_rating"] ??
                            $optionData["order_index"];
                    }

                    $option = $question
                        ->options()
                        ->updateOrCreate(
                            ["id" => $optionData["id"] ?? null],
                            $optionPayload
                        );

                    $optionIds[] = $option->id;
                }

                // Delete removed options
                $question
                    ->options()
                    ->whereNotIn("id", $optionIds)
                    ->delete();
            }

            // Delete removed questions
            $section
                ->questions()
                ->whereNotIn("id", $questionIds)
                ->each(function ($question) {
                    $question->options()->delete();
                    $question->delete();
                });
        }

        // Delete removed sections
        $checklist
            ->sections()
            ->whereNotIn("id", $sectionIds)
            ->each(function ($section) {
                $section->questions()->each(function ($question) {
                    $question->options()->delete();
                    $question->delete();
                });
                $section->delete();
            });

        return [
            "checklist" => $checklist
                ->fresh()
                ->load("sections.questions.options"),
            "has_changes" => $hasChanges,
        ];
    }

    private function normalizeChecklist(Checklist $checklist): array
    {
        return [
            "name" => $checklist->name,
            "sections" => $checklist->sections
                ->map(function ($section) {
                    return [
                        "id" => $section->id,
                        "title" => $section->title,
                        "order_index" => (int) $section->order_index,
                        "category" => $section->category?->name, // relation name -> category name
                        "questions" => $section->questions
                            ->map(function ($q) {
                                return [
                                    "id" => $q->id,
                                    "question_text" => $q->question_text,
                                    "question_type" => $q->question_type,
                                    "order_index" => (int) $q->order_index,
                                    "options" => $q->options
                                        ->map(function ($o) use ($q) {
                                            $opt = [
                                                "id" => $o->id,
                                                "option_text" =>
                                                    $o->option_text,
                                                "order_index" =>
                                                    (int) $o->order_index,
                                            ];
                                            if (
                                                $q->question_type ===
                                                "multiple_choice"
                                            ) {
                                                $opt["score_rating"] =
                                                    (int) ($o->score_rating ??
                                                        $o->order_index);
                                            }
                                            return $opt;
                                        })
                                        ->values()
                                        ->toArray(),
                                ];
                            })
                            ->values()
                            ->toArray(),
                    ];
                })
                ->values()
                ->toArray(),
        ];
    }

    private function normalizeIncoming(array $data): array
    {
        return [
            "name" => $data["name"],
            "sections" => collect($data["sections"])
                ->map(function ($section) {
                    return [
                        "id" => $section["id"] ?? null,
                        "title" => $section["title"],
                        "order_index" => (int) $section["order_index"],
                        "category" => $section["category"] ?? null,
                        "questions" => collect($section["questions"])
                            ->map(function ($q) {
                                return [
                                    "id" => $q["id"] ?? null,
                                    "question_text" => $q["question_text"],
                                    "question_type" => $q["question_type"],
                                    "order_index" => (int) $q["order_index"],
                                    "options" => collect($q["options"] ?? [])
                                        ->map(function ($o) use ($q) {
                                            $opt = [
                                                "id" => $o["id"] ?? null,
                                                "option_text" =>
                                                    $o["option_text"],
                                                "order_index" =>
                                                    (int) $o["order_index"],
                                            ];
                                            if (
                                                $q["question_type"] ===
                                                "multiple_choice"
                                            ) {
                                                $opt["score_rating"] =
                                                    (int) ($o["score_rating"] ??
                                                        $o["order_index"]);
                                            }
                                            return $opt;
                                        })
                                        ->values()
                                        ->toArray(),
                                ];
                            })
                            ->values()
                            ->toArray(),
                    ];
                })
                ->values()
                ->toArray(),
        ];
    }
}
