<?php

namespace App\Http\Controllers\Api\QA;

use App\Models\Area;
use App\Models\Store;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Essa\APIToolKit\Api\ApiResponse;
use App\Http\Requests\QA\StoreRequest;
use App\Services\QAService\QAServices;
use App\Http\Requests\QADisplayRequest;
use App\Http\Resources\Area\QAAreaResource;
use App\Http\Resources\Store\QAStoreResource;
use App\Http\Resources\Weekly\WeeklyQAResource;
use App\Http\Resources\Weekly\WeeklyQAStoreResource;

class QAController extends Controller
{
    use ApiResponse;

    protected QAServices $qaServices;

    public function __construct(QAServices $qaServices)
    {
        $this->qaServices = $qaServices;
    }

    public function index(QADisplayRequest $request)
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
            ->useFilters()
            ->dynamicPaginate();

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

    public function show(Request $request, $id)
    {
        $user_id = Auth()->user()->id;
        $month = $request->month;
        $year = $request->year;
        $week = $request->week;
        $store_checklist_id = $request->store_checklist_id;

        $area = Store::with([
            "store_checklist" => function ($q) use ($store_checklist_id) {
                $q->where("id", $store_checklist_id);
            },
            "store_checklist.weekly_record" => function ($q) use (
                $month,
                $year,
                $week
            ) {
                $q->when($month, fn($query) => $query->where("month", $month))
                    ->when($year, fn($query) => $query->where("year", $year))
                    ->when($week, fn($query) => $query->where("week", $week))
                    ->orderBy("week", "asc");
            },
            "store_checklist.weekly_record.users",
            "store_checklist.weekly_record.weekly_response.staff_on_duty",
        ])->find($id);

        if (!$area) {
            return $this->responseNotFound(__("messages.id_not_found"));
        }

        $checklist = new WeeklyQAStoreResource($area);

        return $this->responseSuccess(
            "Store checklist display successfully.",
            $checklist
        );
    }

    public function filteredByWeek($id)
    {
    }

    public function store(StoreRequest $request)
    {
        $user_id = Auth()->user()->id;

        $answer = $this->qaServices->storeResponse($request->all());

        return $this->responseCreated(
            "Store checklist fill up successfully.",
            $answer
        );
    }

    public function update(StoreRequest $request, $id)
    {
        $user_id = Auth()->user()->id;

        $answer = $this->qaServices->updateResponse($id, $request->all());

        if (is_string($answer)) {
            return $this->responseInvalid($answer);
        }

        return $this->responseSuccess(
            "Store checklist updated successfully.",
            $answer
        );
    }

    public function downloadAttachment(Request $request)
    {
        $filenames = $request->input("filenames", []);
        $zip = $request->input("zip", false);

        // Ensure boolean conversion
        if (is_string($zip)) {
            $zip = $zip === "true" || $zip === "1";
        } else {
            $zip = (bool) $zip;
        }

        return $this->qaServices->downloadAttachment($filenames, $zip);
    }

    public function weeklySkipped(Request $request)
    {
        $user_id = Auth()->user()->id;

        $answer = $this->qaServices->autoSkip($request->all());

        if (is_string($answer)) {
            return $this->responseInvalid($answer);
        }

        return $this->responseCreated(
            "Store checklist skipped successfully.",
            $answer
        );
    }
}
