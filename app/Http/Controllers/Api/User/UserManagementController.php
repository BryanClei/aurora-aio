<?php

namespace App\Http\Controllers\Api\User;

use App\Models\User;
use App\Models\OneCharging;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Essa\APIToolKit\Api\ApiResponse;
use App\Http\Requests\DisplayRequest;
use Illuminate\Support\Facades\Cache;
use App\Http\Requests\User\UserRequest;
use Illuminate\Support\Facades\Artisan;
use App\Http\Resources\User\UserResource;
use App\Services\UserServices\ManagementService;

class UserManagementController extends Controller
{
    use ApiResponse;

    protected ManagementService $managementService;

    public function __construct(ManagementService $managementService)
    {
        $this->managementService = $managementService;
    }

    public function index(DisplayRequest $request)
    {
        $status = $request->status;
        $pagination = $request->pagination;

        $users = User::when($status == "inactive", function ($query) {
            $query->onlyTrashed();
        })
            ->useFilters()
            ->dynamicPaginate();

        if ($users->isEmpty()) {
            return $this->responseNotFound(__("messages.data_not_found"));
        }

        if (!$pagination) {
            UserResource::collection($users);
        } else {
            $users = UserResource::collection($users);
        }
        return $this->responseSuccess("User display successfully", $users);
    }

    public function show($id)
    {
        $user = User::find($id);

        if (!$user) {
            return $this->responseNotFound(__("messages.id_not_found"));
        }

        return $this->responseSuccess(
            "User fetched successfully",
            new UserResource($user)
        );
    }

    public function store(UserRequest $request)
    {
        $user = $this->managementService->createUser($request->all());

        return $this->responseCreated("User Successfully Created", $user);
    }

    public function update(UserRequest $request, $id)
    {
        $user = $this->managementService->updateUser($request->all(), $id);

        if (!$user) {
            return $this->responseUnprocessable(
                "",
                __("messages.id_not_found")
            );
        }

        if (!$user->wasChanged()) {
            return $this->responseSuccess("No Changes", $user);
        }

        return $this->responseSuccess("User successfully updated", $user);
    }

    public function toggleArchived(Request $request, $id)
    {
        $result = $this->managementService->toggleArchived($id);

        if (!$result) {
            return $this->responseUnprocessable(
                "",
                __("messages.id_not_found")
            );
        }

        return $this->responseSuccess($result["message"], $result["user"]);
    }

    public function sedar_employees(Request $request)
    {
        $sedarUsers = Cache::get("sedar_users");

        if (!$sedarUsers) {
            Artisan::call("cache:sedar-users");

            return $this->responseSuccess("Syncing. Refresh the api", "");
        }

        return $this->responseSuccess(
            "Sedar Users display successfully",
            $sedarUsers["data"]
        );
    }
}
