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

class AreaController extends Controller
{
    use ApiResponse;

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

        return $this->responseSuccess("Area display successfully.", $area);
    }

    public function store(AreaRequest $request)
    {
        $area = Area::create([
            "name" => $request->name,
            "region_id" => $request->region_id,
            "area_head_id" => $request->area_head_id,
        ]);

        return $this->responseCreated("Area successfully created", $area);
    }

    public function update(AreaRequest $request, $id)
    {
        $area = Area::find($id);

        if (!$area) {
            return $this->responseUnprocessable(
                "",
                __("messages.id_not_found")
            );
        }

        $area->name = $request->name;
        $area->region_id = $request->region_id;
        $area->area_head_id = $request->area_head_id;

        if (!$area->isDirty()) {
            return $this->responseSuccess("No Changes", $area);
        }

        $area->save();

        return $this->responseSuccess("Area successfully updated.", $area);
    }

    public function toggleArchive($id)
    {
        $area = Area::withTrashed()->find($id);

        if (!$area) {
            return $this->responseUnprocessable(
                "",
                __("messages.id_not_found")
            );
        }

        if ($area->trashed()) {
            $area->restore();

            return $this->responseSuccess(
                __("messages.success_restored", ["attribute" => "Area"]),
                $area
            );
        }

        if (Store::where("area_id", $area->id)->exists()) {
            return $this->responseUnprocessable(
                "",
                "Unable to archive. Area is currently in use."
            );
        }

        $area->delete();

        return $this->responseSuccess(
            __("messages.success_archived", ["attribute" => "Area"]),
            $area
        );
    }
}
