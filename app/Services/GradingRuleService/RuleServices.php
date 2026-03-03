<?php

namespace App\Services\GradingRuleService;

use App\Models\GradingRule;

class RuleServices
{
    public function getAll()
    {
        $grading = GradingRule::all();

        if ($grading->isEmpty()) {
            return null;
        }

        return $grading;
    }

    public function create(array $data)
    {
        $grade = GradingRule::create([
            "cap_percentage" => $data["cap_percentage"],
        ]);

        return $grade;
    }

    public function update($id, array $data)
    {
        $grade = GradingRule::find($id);

        if (!$grade) {
            return null;
        }

        $grade->update([
            "cap_percentage" => $data["cap_percentage"],
        ]);

        return $grade;
    }
}
