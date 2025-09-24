<?php

namespace App\Services\ChecklistServices;

use App\Models\Checklist;

class ChecklistService
{
    public function createChecklist(array $data): array
    {
        $new_checklist = Checklist::create([
            "name" => $data["name"],
        ]);

        foreach ($data["sections"] as $sectionData) {
            $section = $new_checklist->sections()->create([
                "title" => $sectionData["title"],
                "checklist_id" => $new_checklist->id,
                "order_index" => $sectionData["order_index"],
            ]);

            foreach ($sectionData["questions"] as $questionData) {
                $question = $section->questions()->create([
                    "section_id" => $section->id,
                    "question_text" => $questionData["question_text"],
                    "question_type" => $questionData["question_type"],
                    "order_index" => $questionData["order_index"],
                ]);

                if (
                    in_array($questionData["question_type"], [
                        "multiple_choice",
                        "checkboxes",
                    ]) &&
                    isset($questionData["options"])
                ) {
                    foreach ($questionData["options"] as $optionData) {
                        $question->options()->create([
                            "question_id" => $question->id,
                            "option_text" => $optionData["option_text"],
                            "order_index" => $optionData["order_index"],
                        ]);
                    }
                }
            }
        }

        $new_checklist->fresh()->load(["sections.questions.options"]);

        return ["checklist" => $new_checklist];
    }

    public function updateChecklist(int $checklistId, array $data): array
    {
        $checklist = Checklist::with("sections.questions.options")->findOrFail(
            $checklistId
        );

        $existingData = $this->normalizeChecklist($checklist);
        $incomingData = [
            "name" => $data["name"],
            "sections" => collect($data["sections"])
                ->map(
                    fn($section) => [
                        "title" => $section["title"],
                        "order_index" => $section["order_index"],
                        "questions" => collect($section["questions"])
                            ->map(
                                fn($question) => [
                                    "question_text" =>
                                        $question["question_text"],
                                    "question_type" =>
                                        $question["question_type"],
                                    "order_index" => $question["order_index"],
                                    "options" => collect(
                                        $question["options"] ?? []
                                    )
                                        ->map(
                                            fn($option) => [
                                                "option_text" =>
                                                    $option["option_text"],
                                                "order_index" =>
                                                    $option["order_index"],
                                            ]
                                        )
                                        ->toArray(),
                                ]
                            )
                            ->toArray(),
                    ]
                )
                ->toArray(),
        ];

        $hasChanges = json_encode($existingData) !== json_encode($incomingData);

        if (!$hasChanges) {
            return [
                "checklist" => $checklist,
                "has_changes" => false,
            ];
        }

        $checklist->update(["name" => $data["name"]]);

        $checklist->sections()->forceDelete();
        foreach ($data["sections"] as $sectionData) {
            $section = $checklist->sections()->create([
                "title" => $sectionData["title"],
                "order_index" => $sectionData["order_index"],
            ]);

            foreach ($sectionData["questions"] as $questionData) {
                $question = $section->questions()->create([
                    "question_text" => $questionData["question_text"],
                    "question_type" => $questionData["question_type"],
                    "order_index" => $questionData["order_index"],
                ]);

                if (
                    in_array($questionData["question_type"], [
                        "multiple_choice",
                        "checkboxes",
                    ]) &&
                    isset($questionData["options"])
                ) {
                    foreach ($questionData["options"] as $optionData) {
                        $question->options()->create([
                            "option_text" => $optionData["option_text"],
                            "order_index" => $optionData["order_index"],
                        ]);
                    }
                }
            }
        }

        return [
            "checklist" => $checklist
                ->fresh()
                ->load(["sections.questions.options"]),
            "has_changes" => true,
        ];
    }

    private function normalizeChecklist(Checklist $checklist): array
    {
        $sections = $checklist->sections ?? collect();

        return [
            "name" => $checklist->name,
            "sections" => $sections
                ->map(
                    fn($section) => [
                        "title" => $section->title,
                        "order_index" => $section->order_index,
                        "questions" => ($section->questions ?? collect())
                            ->map(
                                fn($question) => [
                                    "question_text" => $question->question_text,
                                    "question_type" => $question->question_type,
                                    "order_index" => $question->order_index,
                                    "options" => (
                                        $question->options ?? collect()
                                    )
                                        ->map(
                                            fn($option) => [
                                                "option_text" =>
                                                    $option->option_text,
                                                "order_index" =>
                                                    $option->order_index,
                                            ]
                                        )
                                        ->toArray(),
                                ]
                            )
                            ->toArray(),
                    ]
                )
                ->toArray(),
        ];
    }
}
