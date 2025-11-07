<?php

namespace App\Http\Controllers\Api\RegionAreaHead;

use App\Models\Area;
use App\Models\Region;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Essa\APIToolKit\Api\ApiResponse;
use App\Http\Resources\Area\QAAreaResource;
use App\Http\Resources\Region\QARegionResource;
use App\Http\Requests\RegionAreaHeadDisplayRequest;

class RegionAreaHeadController extends Controller
{
    use ApiResponse;

    public function index(RegionAreaHeadDisplayRequest $request)
    {
        $user_id = Auth()->user()->id;
        $user_type = $request->user_type;
        $pagination = $request->pagination;

        $query =
            $user_type === "region_head"
                ? Region::with(["area.area_head", "region_head"])->whereHas(
                    "region_head",
                    fn($q) => $q->where("id", $user_id)
                )
                : Area::with(["region.region_head", "area_head"])->where(
                    "area_head_id",
                    $user_id
                );

        $data = $query->useFilters()->dynamicPaginate();

        if ($data->isEmpty()) {
            return $this->responseNotFound(__("messages.data_not_found"));
        }

        $message_type = $user_type === "region_head" ? "Region" : "Area";

        if (!$pagination) {
            $user_type === "region_head"
                ? QARegionResource::collection($data)
                : QAAreaResource::collection($data);
        } else {
            $data =
                $user_type === "region_head"
                    ? QARegionResource::collection($data)
                    : QAAreaResource::collection($data);
        }

        return $this->responseSuccess(
            __("messages.success_display", ["attribute" => $message_type]),
            $data
        );
    }
}
