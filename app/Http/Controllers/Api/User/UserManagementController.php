<?php

namespace App\Http\Controllers\Api\User;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Essa\APIToolKit\Api\ApiResponse;
use App\Http\Requests\DisplayRequest;
use App\Http\Requests\User\UserRequest;
use App\Http\Resources\User\UserResource;

class UserManagementController extends Controller
{
    use ApiResponse;

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

    public function store(UserRequest $request)
    {
        $create_user = User::create([
            "id_prefix" => $request["personal_info"]["id_prefix"],
            "id_no" => $request["personal_info"]["id_no"],
            "first_name" => $request["personal_info"]["first_name"],
            "middle_name" => $request["personal_info"]["middle_name"],
            "last_name" => $request["personal_info"]["last_name"],
            "suffix" => $request["personal_info"]["suffix"],
            "mobile_number" => $request["personal_info"]["mobile_number"],
            "gender" => $request["personal_info"]["gender"],
            "one_charging_id" => $request["personal_info"]["one_charging_id"],
            "username" => $request["username"],
            "password" => $request["username"],
            "role_id" => $request["role_id"],
        ]);

        return $this->responseCreated(
            "User Successfully Created",
            $create_user
        );
    }

    public function update(UserRequest $request, $id)
    {
        $user = User::find($id);

        if (!$user) {
            return $this->responseUnprocessable(
                "",
                __("messages.id_not_found")
            );
        }

        $data = [
            "mobile_number" => $request["personal_info"]["mobile_number"],
            "one_charging_id" => $request["personal_info"]["one_charging_id"],
            "username" => $request["username"],
            "role_id" => $request["role_id"],
        ];

        $user->fill($data);

        if (!$user->isDirty()) {
            return $this->responseSuccess("No Changes", $user);
        }

        $user->save();

        return $this->responseSuccess("User successfully updated", $user);
    }

    public function toggleArchived(Request $request, $id)
    {
        $user = User::withTrashed()->find($id);

        if (!$user) {
            return $this->responseUnprocessable(
                "",
                __("messages.id_not_found")
            );
        }

        if ($user->trashed()) {
            $user->restore();
            $message = "User successfully restored.";
        } else {
            $user->delete();
            $message = "User successfully archived.";
        }

        return $this->responseSuccess($message, $user);
    }
}
