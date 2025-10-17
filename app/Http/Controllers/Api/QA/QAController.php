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

        $area = Area::with([
            "store.store_checklist.weekly_record" => function ($q) use (
                $month,
                $year
            ) {
                $q->when($month, fn($query) => $query->where("month", $month))
                    ->when($year, fn($query) => $query->where("year", $year))
                    ->orderBy("week", "asc");
            },
        ])
            ->useFilters()
            ->dynamicPaginate();

        if (!$pagination) {
            QAAreaResource::collection($area);
        } else {
            $area = QAAreaResource::collection($area);
        }

        return $this->responseSuccess(
            "Store checklist display successfully.",
            $area
        );
    }

    public function show(Request $request, $id)
    {
        $user_id = Auth()->user()->id;
        $month = $request->month;
        $year = $request->year;
        $week = $request->week;

        $area = Store::with([
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
            "store_checklist.weekly_record.weekly_response",
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
}
