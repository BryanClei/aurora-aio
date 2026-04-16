<?php

namespace App\Http\Controllers\Api\QA;

use App\Http\Controllers\Controller;
use App\Http\Requests\QA\StoreRequest;
use App\Http\Requests\QA\UpdateRequest;
use App\Http\Requests\QADisplayRequest;
use App\Http\Requests\ReasonRequest;
use App\Http\Resources\Store\QAStoreResource;
use App\Http\Resources\Weekly\WeeklyQAStoreResource;
use App\Models\Store;
use App\Services\QAService\QAServices;
use Essa\APIToolKit\Api\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
        $user_id = Auth::user()->id;
        $pagination = $request->pagination;
        $month = $request->month;
        $year = $request->year;

        // $store = Store::with([
        //     "store_checklist.weekly_record" => function ($q) use (
        //         $month,
        //         $year
        //     ) {
        //         $q->when($month, fn($query) => $query->where("month", $month))
        //             ->when($year, fn($query) => $query->where("year", $year))
        //             ->orderBy("week", "asc");
        //     },
        //     "store_checklist.weekly_record.weekly_skipped",
        // ])
        //     ->whereHas("store_checklist", function ($query) {
        //         $query->whereHas("checklist.sections");
        //     })
        //     ->useFilters()
        //     ->dynamicPaginate();

        $store = Store::with([
            "store_checklist" => function ($q) use ($month, $year) {
                $q->withExists([
                    'previous_overdue as has_previous_overdue' => function ($query) use ($month, $year) {
                        $query->where('status', 'Overdue')
                            ->where(function ($q2) use ($month, $year) {
                                $q2->where('year', '<', $year)
                                    ->orWhere(fn($q3) => $q3->where('year', $year)->where('month', '<', $month));
                            });
                    }
                ]);
            },
            "store_checklist.weekly_record" => function ($q) use ($month, $year) {
                $q->when($month, fn($query) => $query->where("month", $month))
                    ->when($year, fn($query) => $query->where("year", $year))
                    ->orderBy("week", "asc");
            },
            "store_checklist.weekly_record.weekly_skipped",
        ])
            ->useFilters()
            ->dynamicPaginate();

        if ($store->isEmpty()) {
            return $this->responseNotFound("No store checklist found.");
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

    public function show(Request $request, $id)
    {
        $user_id = Auth::user()->id;
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
            "store_checklist.weekly_record.weekly_skipped",
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

    public function filteredByWeek($id) {}

    public function store(StoreRequest $request)
    {
        $request->all();

        $user_id = Auth::user()->id;

        $answer = $this->qaServices->storeResponse($request->all());

        if (!$answer["success"]) {
            return $this->responseUnprocessable($answer["message"]);
        }

        return $this->responseCreated(
            "Store checklist fill up successfully.",
            $answer
        );
    }

    public function update(StoreRequest $request, $id)
    {
        $user_id = Auth::user()->id;

        $answer = $this->qaServices->updateResponse($id, $request->all());

        if (is_string($answer)) {
            return $this->responseUnprocessable($answer);
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

        if (is_string($zip)) {
            $zip = $zip === "true" || $zip === "1";
        } else {
            $zip = (bool) $zip;
        }

        return $this->qaServices->downloadAttachment($filenames, $zip);
    }

    public function viewSingleAttachment(Request $request)
    {
        $filename = $request->input("filename");

        return $this->qaServices->viewSingleAttachment($filename);
    }

    public function weeklySkipped(Request $request)
    {
        $user_id = Auth::user()->id;

        $answer = $this->qaServices->autoSkip($request->all());

        if (is_string($answer)) {
            return $this->responseUnprocessable($answer);
        }

        return $this->responseCreated(
            "Store checklist skipped successfully.",
            $answer
        );
    }

    public function forApproval(ReasonRequest $request, $id)
    {
        $user_id = Auth::user()->id;

        $answer = $this->qaServices->forApproval($id, $request->all());

        if (is_string($answer)) {
            return $this->responseUnprocessable($answer);
        }

        return $this->responseSuccess(
            "Store checklist submitted for approval successfully.",
            $answer
        );
    }

    public function addSignature(Request $request, $id)
    {
        $weekly_record = $this->qaServices->addSignature(
            $id,
            $request->file('signature'),      // file (nullable)
            $request->input('signature')       // text "true" (nullable)
        );

        if (!$weekly_record) {
            return $this->responseNotFound(__("messages.id_not_found"));
        }

        if (is_string($weekly_record)) {
            return $this->responseUnprocessable($weekly_record);
        }

        return $this->responseSuccess(
            "Signature added successfully.",
            $weekly_record
        );
    }

    public function viewAttachment($id)
    {
        $attachment = $this->qaServices->viewAttachment($id);

        if (!$attachment) {
            return $this->responseNotFound(
                __("messages.data_not_found")
            );
        }

        return $attachment;
    }
}
