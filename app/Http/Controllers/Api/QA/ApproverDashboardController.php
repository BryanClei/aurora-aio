<?php

namespace App\Http\Controllers\Api\QA;

use App\Models\AutoSkipped;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Essa\APIToolKit\Api\ApiResponse;
use App\Http\Requests\ApproverDisplayRequest;
use App\Services\ApproverServices\AutoSkippedService;

class ApproverDashboardController extends Controller
{
    use ApiResponse;

    protected AutoSkippedService $autoSkippedService;

    public function __construct(AutoSkippedService $autoSkippedService)
    {
        $this->autoSkippedService = $autoSkippedService;
    }

    public function index(ApproverDisplayRequest $request)
    {
        $skipped_records = AutoSkipped::with("weeklyRecord.storeChecklist")
            ->useFilters()
            ->dynamicPaginate();

        if ($skipped_records->isEmpty()) {
            return $this->responseNotFound("", __("messages.data_not_found"));
        }

        return $this->responseSuccess(
            "Store checklist display successfully.",
            $skipped_records
        );
    }

    public function approved($id)
    {
        $skipped_records = AutoSkipped::find($id);

        if (!$skipped_records) {
            return $this->responseNotFound("", __("messages.id_not_found"));
        }

        return $approved_record = $this->autoSkippedService->approvedFunction(
            "Store checklist survey rejected successfully.",
            $skipped_records
        );
    }

    public function reject($id)
    {
        $skipped_records = AutoSkipped::find($id);

        if (!$skipped_records) {
            return $this->responseNotFound("", __("messages.id_not_found"));
        }

        return $approved_record = $this->autoSkippedService->approvedFunction(
            "Store checklist survey rejected successfully.",
            $skipped_records
        );
    }
}
