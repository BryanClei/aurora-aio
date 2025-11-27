<?php

namespace App\Http\Controllers\Api\QA;

use App\Models\Store;
use App\Models\AutoSkipped;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Essa\APIToolKit\Api\ApiResponse;
use App\Http\Requests\ApproverDisplayRequest;
use App\Http\Resources\Store\QAStoreResource;
use App\Services\ApproverServices\AutoSkippedService;

class ApproverDashboardController extends Controller
{
    use ApiResponse;

    protected AutoSkippedService $autoSkippedService;

    public function __construct(AutoSkippedService $autoSkippedService)
    {
        $this->autoSkippedService = $autoSkippedService;
    }

    public function index(Request $request)
    {
        $user_id = Auth()->user()->id;
        $pagination = $request->pagination;
        $month = $request->month;
        $year = $request->year;

        $store = Store::with([
            "store_checklist.weekly_record" => function ($q) use (
                $month,
                $year
            ) {
                $q->when($month, fn($query) => $query->where("month", $month))
                    ->when($year, fn($query) => $query->where("year", $year))
                    ->orderBy("week", "asc");
            },
            "store_checklist.weekly_record.weekly_skipped",
        ])
            ->whereHas("store_checklist", function ($query) {
                $query->whereHas("checklist.sections");
            })
            ->whereHas("store_checklist.weekly_record", function ($query) {
                $query->where("status", "For Approval");
            })
            ->whereHas("store_checklist.weekly_record.weekly_skipped")
            ->useFilters()
            ->dynamicPaginate();

        if ($store->isEmpty()) {
            return $this->responseNotFound("", __("messages.no_data_found"));
        }

        if (!$pagination) {
            QAStoreResource::collection($store);
        } else {
            $store = QAStoreResource::collection($store);
        }

        return $this->responseSuccess(
            "Store checklist display successfully.",
            $store
        );
    }

    public function approved(Request $request, $id)
    {
        $skipped_records = AutoSkipped::find($id);

        if (!$skipped_records) {
            return $this->responseNotFound("", __("messages.id_not_found"));
        }

        $approved_record = $this->autoSkippedService->approvedFunction(
            $skipped_records,
            $request->all()
        );

        return $this->responseSuccess(
            "Record approved successfully.",
            $approved_record
        );
    }

    public function rejected(Request $request, $id)
    {
        $skipped_records = AutoSkipped::find($id);

        if (!$skipped_records) {
            return $this->responseNotFound("", __("messages.id_not_found"));
        }

        $rejected_record = $this->autoSkippedService->rejectedFunction(
            $skipped_records,
            $request->all()
        );

        return $this->responseSuccess(
            "Record rejected successfully.",
            $rejected_record
        );
    }
}
