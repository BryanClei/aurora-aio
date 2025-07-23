<?php

namespace App\Http\Controllers\Api\Region;

use App\Models\Region;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Essa\APIToolKit\Api\ApiResponse;
use App\Http\Requests\DisplayRequest;
use App\Http\Requests\Region\RegionRequest;
use App\Http\Resources\Region\RegionResource;

class RegionController extends Controller
{
    use ApiResponse;

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

    public function show()
    {
    }

    public function store(RegionRequest $request)
    {
        $new_region = Region::create([
            "name" => $request->name,
            "region_head_id" => $request->region_head_id,
        ]);

        return $this->responseCreated(
            "Region successfully created.",
            $new_region
        );
    }

    public function update(RegionRequest $request, $id)
    {
        $region = Region::find($id);

        if (!$region) {
            return $this->responseUnprocessable(
                "",
                __("messages.id_not_found")
            );
        }

        $region->name = $request->name;
        $region->region_head_id = $request->region_head_id;

        if (!$region->isDirty()) {
            return $this->responseSuccess("No Changes", $region);
        }

        $region->save();

        return $this->responseSuccess("Region successfully updated", $region);
    }

    public function toggleArchive(Request $request, $id)
    {
        $region = Region::withTrashed()->find($id);

        if (!$region) {
            return $this->responseUnprocessable(
                "",
                __("messages.id_not_found")
            );
        }

        if ($region->trashed()) {
            $region->restore();
            $message = __("messages.success_restored", [
                "attribute" => "Region",
            ]);
        } else {
            $region->delete();
            $message = __("messages.success_archived", [
                "attribute" => "Region",
            ]);
        }

        return $this->responseSuccess($message, $region);
    }
}
