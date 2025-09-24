<?php

namespace App\Http\Controllers\Api\Store;

use App\Models\Store;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Essa\APIToolKit\Api\ApiResponse;
use App\Http\Requests\DisplayRequest;
use App\Http\Requests\Store\StoreRequest;
use App\Http\Resources\Store\StoreResource;
use App\Services\StoreServices\StoreService;

class StoreController extends Controller
{
    use ApiResponse;

    protected $storeService;

    public function __construct(StoreService $storeService)
    {
        $this->storeService = $storeService;
    }

    public function index(DisplayRequest $request)
    {
        $status = $request->status;
        $pagination = $request->pagination;

        $store = Store::when($status == "inactive", function ($query) {
            $query->onlyTrashed();
        })
            ->useFilters()
            ->dynamicPaginate();

        if ($store->isEmpty()) {
            return $this->responseNotFound(__("messages.data_not_found"));
        }

        if (!$pagination) {
            StoreResource::collection($store);
        } else {
            $store = StoreResource::collection($store);
        }

        return $this->responseSuccess("Store display successfully", $store);
    }

    public function show()
    {
    }

    public function store(StoreRequest $request)
    {
        return "hello world";
    }

    public function update()
    {
    }

    public function toggleArchived()
    {
    }
}
