<?php

namespace App\Http\Controllers\Api\Area;

use App\Models\Area;
use App\Models\Store;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Essa\APIToolKit\Api\ApiResponse;
use App\Http\Requests\DisplayRequest;
use App\Http\Requests\Area\AreaRequest;
use App\Http\Resources\Area\AreaResource;
use App\Services\AreaServices\AreaService;

class AreaController extends Controller
{
    use ApiResponse;

    protected AreaService $areaService;

    public function __construct(AreaService $areaService)
    {
        $this->areaService = $areaService;
    }

    public function index(DisplayRequest $request)
    {
        $status = $request->status;
        $pagination = $request->pagination;

        $areas = Area::when($status == "inactive", function ($query) {
            $query->onlyTrashed();
        })
            ->useFilters()
            ->dynamicPaginate();

        if ($areas->isEmpty()) {
            return $this->responseNotFound(__("messages.data_not_found"));
        }

        if (!$pagination) {
            AreaResource::collection($areas);
        } else {
            $areas = AreaResource::collection($areas);
        }

        return $this->responseSuccess("Area successfully display", $areas);
    }

    public function show($id)
    {
        $area = Area::find($id);

        if (!$area) {
            return $this->responseNotFound(__("messages.id_not_found"));
        }

        $transform_data = new AreaResource($area);

        return $this->responseSuccess(
            "Area display successfully.",
            $transform_data
        );
    }

    public function store(AreaRequest $request)
    {
        $area = $this->areaService->createArea($request->all());

        return $this->responseCreated(
            "Area successfully created",
            $area["area"]
        );
    }

    public function update(AreaRequest $request, $id)
    {
        $area = $this->areaService->updateArea($id, $request->all());

        if (!$area) {
            return $this->responseUnprocessable(
                "",
                __("messages.id_not_found")
            );
        }

        return $this->responseSuccess($area["message"], $area["data"]);
    }

    public function toggleArchive($id)
    {
        $area = $this->areaService->toggleArchived($id);

        if (!$area) {
            return $this->responseUnprocessable(
                "",
                __("messages.id_not_found")
            );
        }

        return $this->responseSuccess($area["message"], $area["area"]);
    }
}
