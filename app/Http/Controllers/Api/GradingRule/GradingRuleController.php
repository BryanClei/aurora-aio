<?php

namespace App\Http\Controllers\Api\GradingRule;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Essa\APIToolKit\Api\ApiResponse;
use App\Http\Requests\GradingRule\RuleRequest;
use App\Services\GradingRuleService\RuleServices;

class GradingRuleController extends Controller
{
    use ApiResponse;

    protected RuleServices $ruleServices;

    public function __construct(RuleServices $ruleServices)
    {
        $this->ruleServices = $ruleServices;
    }

    public function index()
    {
        $grades = $this->ruleServices->getAll();

        if (!$grades) {
            return $this->responseNotFound("No grading rules found.");
        }

        return $this->responseSuccess(
            "Grading rules display successfully.",
            $grades
        );
    }

    public function store(RuleRequest $request)
    {
        $grade = $this->ruleServices->create($request->all());

        return $this->responseCreated(
            "Grade rule created successfully.",
            $grade
        );
    }

    public function update(RuleRequest $request, $id)
    {
        $grade = $this->ruleServices->update($id, $request->all());

        return $this->responseSuccess(
            "Grading rule updated successfully.",
            $grade
        );
    }
}
