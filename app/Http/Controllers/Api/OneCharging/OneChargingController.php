<?php

namespace App\Http\Controllers\Api\OneCharging;

use Carbon\Carbon;
use App\Models\OneCharging;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Essa\APIToolKit\Api\ApiResponse;
use Illuminate\Support\Facades\Http;
use App\Http\Requests\DisplayRequest;
use App\Services\OneChargingService\OneService;

class OneChargingController extends Controller
{
    use ApiResponse;

    protected OneService $oneService;

    public function __construct(OneService $oneService)
    {
        $this->oneService = $oneService;
    }

    public function index(DisplayRequest $request)
    {
        $status = $request->status;

        $one_charging = OneCharging::when($status == "inactive", function (
            $query
        ) {
            $query->onlyTrashed();
        })
            ->orderBy("updated_at")
            ->useFilters()
            ->dynamicPaginate();

        if ($one_charging->isEmpty()) {
            return $this->responseNotFound(__("messages.data_not_found"));
        }

        return $this->responseSuccess(
            "One charging display successfully.",
            $one_charging
        );
    }

    public function show($id)
    {
        $one_charging = OneCharging::find($id);

        if (!$one_charging) {
            return $this->responseNotFound(__("messages.id_not_found"));
        }

        return $this->responseSuccess(
            "One charging display successfully.",
            $one_charging
        );
    }

    public function sync()
    {
        $result = $this->oneService->sync();

        if (!$result["success"]) {
            return $this->responseServerError($result["message"]);
        }

        return $this->responseSuccess($result["message"], $result["data"]);
    }
}
