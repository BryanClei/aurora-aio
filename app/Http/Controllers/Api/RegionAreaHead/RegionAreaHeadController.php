<?php

namespace App\Http\Controllers\Api\RegionAreaHead;

use App\Models\Area;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Essa\APIToolKit\Api\ApiResponse;
use App\Http\Resources\Area\QAAreaResource;
use App\Http\Requests\RegionAreaHeadDisplayRequest;

class RegionAreaHeadController extends Controller
{
    use ApiResponse;

    public function index(RegionAreaHeadDisplayRequest $request)
    {
        $user_id = Auth()->user()->id;
        $user_type = $request->user_type;
        $pagination = $request->pagination;

        $area = Area::with("region.region_head", "area_head")
            ->when($user_type === "region_head", function ($query) use (
                $user_id
            ) {
                $query->whereHas("region.region_head", function ($query) use (
                    $user_id
                ) {
                    $query->where("id", $user_id);
                });
            })
            ->when($user_type === "area_head", function ($query) use (
                $user_id
            ) {
                $query->whereHas("area_head", function ($query) use ($user_id) {
                    $query->where("id", $user_id);
                });
            })
            ->useFilters()
            ->dynamicPaginate();

        if ($area->isEmpty()) {
            return $this->responseNotFound(__("messages.data_not_found"));
        }

        if (!$pagination) {
            QAAreaResource::collection($area);
        } else {
            $area = QAAreaResource::collection($area);
        }

        return $this->responseSuccess("Area display successfully.", $area);
    }
}
