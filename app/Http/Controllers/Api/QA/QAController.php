<?php

namespace App\Http\Controllers\Api\QA;

use App\Models\Area;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Essa\APIToolKit\Api\ApiResponse;
use App\Services\QAService\QAServices;
use App\Http\Requests\QADisplayRequest;
use App\Http\Resources\Area\QAAreaResource;

class QAController extends Controller
{
    use ApiResponse;

    protected QAServices $qaServices;

    public function __construct(QAServices $qaServices)
    {
        $this->qaServices = $qaServices;
    }

    public function index(QADisplayRequest $request)
    {
        $user_id = Auth()->user()->id;
        $pagination = $request->pagination;

        $area = Area::useFilters()->dynamicPaginate();

        if (!$pagination) {
            QAAreaResource::collection($area);
        } else {
            $area = QAAreaResource::collection($area);
        }

        return $this->responseSuccess("Area display successfully.", $area);
    }

    public function store(Request $request)
    {
        $user_id = Auth()->user()->id;

        $answer = $this->qaServices->storeResponse($request->all());

        return $this->responseCreated(
            "Store checklist fill up successfully.",
            $answer
        );
    }
}
