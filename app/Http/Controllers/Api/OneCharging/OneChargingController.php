<?php

namespace App\Http\Controllers\Api\OneCharging;

use App\Http\Controllers\Controller;
use App\Http\Requests\Charging\ChangePasswordRequest;
use App\Http\Requests\Charging\OneRdfDisplay;
use App\Http\Requests\Charging\OneUserStoreRequest;
use App\Http\Requests\DisplayRequest;
use App\Models\OneCharging;
use App\Services\OneChargingService\OneService;
use Carbon\Carbon;
use Essa\APIToolKit\Api\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

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

    public function oneRdfUserIndex(OneRdfDisplay $request)
    {
        $result = $this->oneService->oneRdfUserIndex($request->all());

        if (!$result) {
            return $this->responseNotFound(__("messages.data_not_found"));
        }

        return $this->responseSuccess("User display successfully.", $result);
    }

    public function oneRdfUserShow($id)
    {
        $result = $this->oneService->oneRdfUserShow($id);

        if (!$result) {
            return $this->responseNotFound("User ID not found.");
        }

        return $this->responseSuccess("User display successfully.", $result);
    }

    public function oneRdfUserSync(OneUserStoreRequest $request)
    {
        $result = $this->oneService->userSync($request->all());

        if (isset($result["created"])) {
            return $this->responseCreated("User created successfully.", $result);
        }

        if (isset($result["updated"]) && $result["updated"] === true) {
            return $this->responseSuccess("User updated successfully.", $result);
        }

        return $this->responseSuccess("No changes detected.", $result);
    }

    public function changePassword(ChangePasswordRequest $request, $id)
    {
        $result = $this->oneService->changePassword($request->all(), $id);

        if (!$result) {
            return $this->responseNotFound(__("messages.id_not_found"));
        }

        return $this->responseSuccess("Password updated successfully.", $result);
    }

    public function resetPassword(Request $request, $id)
    {
        $result = $this->oneService->resetPassword($id);

        if (!$result) {
            return $this->responseNotFound(__("messages.id_not_found"));
        }

        return $this->responseSuccess("Password reset successfully.", $result);
    }
}
