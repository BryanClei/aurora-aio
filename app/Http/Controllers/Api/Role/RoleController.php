<?php

namespace App\Http\Controllers\Api\Role;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\Role\RoleRequest;

class RoleController extends Controller
{
    public function index()
    {
    }

    public function store(RoleRequest $request)
    {
        return "me";

        $create_role = Role::create([
            "name" => $request->name,
            "access_permission" => $request->access_permission,
        ]);

        return $this->responseCreated(
            "Role Successfully Created",
            $create_role
        );
    }

    public function update()
    {
    }
}
