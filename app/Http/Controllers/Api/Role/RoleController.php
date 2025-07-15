<?php

namespace App\Http\Controllers\Api\Role;

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Essa\APIToolKit\Api\ApiResponse;
use App\Http\Requests\DisplayRequest;
use App\Http\Requests\Role\RoleRequest;

class RoleController extends Controller
{
    use ApiResponse;

    public function index(DisplayRequest $request)
    {
        $status = $request->status;

        $role = Role::when($status == "inactive", function ($query) {
            $query->onlyTrashed();
        })
            ->orderBy("created_at", "desc")
            ->useFilters()
            ->dynamicPaginate();

        if ($role->isEmpty()) {
            return $this->responseNotFound(__("messages.data_not_found"));
        }

        return $this->responseSuccess("Role display successfully.", $role);
    }

    public function store(RoleRequest $request)
    {
        $create_role = Role::create([
            "name" => $request->name,
            "access_permission" => $request->access_permission,
        ]);

        return $this->responseCreated(
            "Role Successfully Created",
            $create_role
        );
    }

    public function update(RoleRequest $request, $id)
    {
        $role = Role::find($id);

        if (!$role) {
            return $this->responseUnprocessable(
                "",
                "Invalid ID provided for updating. Please check the ID and try again"
            );
        }

        $previousName = $role->name;

        $role->name = $request["name"];
        $role->access_permission = $request["access_permission"];

        if (!$role->isDirty()) {
            return $this->responseSuccess("No Changes", $role);
        }

        $role->save();

        return $this->responseSuccess("Role successfully updated", $role);
    }

    public function toggleArchive(Request $request, $id)
    {
        $role = Role::withTrashed()->find($id);

        if (!$role) {
            return $this->responseUnprocessable(
                "",
                "Invalid ID. Please check and try again."
            );
        }

        if ($role->trashed()) {
            $role->restore();
            return $this->responseSuccess("Role successfully restored.", $role);
        }

        if (User::where("role_id", $id)->exists()) {
            return $this->responseUnprocessable(
                "",
                "Unable to archive. Role is currently in use."
            );
        }

        $role->delete();
        return $this->responseSuccess("Role successfully archived.", $role);
    }
}
