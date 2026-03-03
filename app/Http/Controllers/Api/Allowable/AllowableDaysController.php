<?php

namespace App\Http\Controllers\Api\Allowable;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Essa\APIToolKit\Api\ApiResponse;
use App\Http\Requests\Allowable\AllowableRequest;
use App\Services\AllowableServices\AllowableService;

class AllowableDaysController extends Controller
{
    use ApiResponse;

    protected AllowableService $allowableService;

    public function __construct(AllowableService $allowableService)
    {
        $this->allowableServices = $allowableService;
    }

    public function index()
    {
        $allowable = $this->allowableServices->getData();

        return $this->responseSuccess(
            "Allowable days display successfully.",
            $allowable
        );
    }

    public function store(AllowableRequest $request)
    {
        $allowable = $this->allowableServices->create($request->all());

        return $this->responseCreated(
            "Allowable days created successfully.",
            $allowable
        );
    }

    public function update(AllowableRequest $request, $id)
    {
        $allowable = $this->allowableServices->update($id, $request->all());

        if (!$allowable) {
            return $this->responseNotFound("Allowable days not found.");
        }

        return $this->responseSuccess(
            "Allowable days updated successfully.",
            $allowable
        );
    }
}
