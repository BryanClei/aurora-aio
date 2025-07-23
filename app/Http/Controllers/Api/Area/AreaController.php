<?php

namespace App\Http\Controllers\Api\Area;

use App\Models\Area;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Essa\APIToolKit\Api\ApiResponse;
use App\Http\Requests\DisplayRequest;
use App\Http\Requests\Area\AreaRequest;

class AreaController extends Controller
{
    use ApiResponse;

    public function index(DisplayRequest $request)
    {
        $stats = $request->status;
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
            $areas = AreaResource::collection($area);
        }

        return $this->responseSuccess("Area successfully display", $area);
    }

    public function show()
    {
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

        
    }
}
