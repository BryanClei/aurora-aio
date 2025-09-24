<?php

namespace App\Http\Controllers\Api\Region;

use App\Models\Area;
use App\Models\Region;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Essa\APIToolKit\Api\ApiResponse;
use App\Http\Requests\DisplayRequest;
use App\Http\Requests\Region\RegionRequest;
use App\Http\Resources\Region\RegionResource;
use App\Services\RegionServices\RegionService;

class RegionController extends Controller
{
    use ApiResponse;

    protected RegionService $regionService;

    public function __construct(RegionService $regionService)
    {
        $this->regionService = $regionService;
    }

    public function index(DisplayRequest $request)
    {
        $status = $request->status;
        $pagination = $request->pagination;

        $region = Region::when($status == "inactive", function ($query) {
            $query->onlyTrashed();
        })
            ->useFilters()
            ->dynamicPaginate();

        if ($region->isEmpty()) {
            return $this->responseNotFound(__("messages.data_not_found"));
        }

        if (!$pagination) {
            RegionResource::collection($region);
        } else {
            $region = RegionResource::collection($region);
        }

        return $this->responseSuccess("Regions display successfully", $region);
    }

    public function show($id)
    {
        $region = Region::find($id);

        if (!$region) {
            return $this->responseNotFound(__("messages.id_not_found"));
        }

        $transform_data = new RegionResource($region);

        return $this->responseSuccess(
            "Region display successfully.",
            $transform_data
        );
    }

    public function store(RegionRequest $request)
    {
        $new_region = $this->regionService->createRegion($request->all());

        return $this->responseCreated(
            "Region successfully created.",
            $new_region["region"]
        );
    }

    public function update(RegionRequest $request, $id)
    {
        $region = $this->regionService->updateRegion($id, $request->all());

        if (!$region) {
            return $this->responseUnprocessable(
                "",
                __("messages.id_not_found")
            );
        }

        return $this->responseSuccess($region["message"], $region["region"]);
    }

    public function toggleArchive(Request $request, $id)
    {
        $region = $this->regionService->toggleArchived($id);

        if (!$region) {
            return $this->responseUnprocessable(
                "",
                __("messages.id_not_found")
            );
        }

        return $this->responseSuccess($region["message"], $region["region"]);
    }
}
