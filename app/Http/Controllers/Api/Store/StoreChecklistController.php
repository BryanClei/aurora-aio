<?php

namespace App\Http\Controllers\Api\Store;

use Illuminate\Http\Request;
use App\Models\StoreChecklist;
use App\Http\Controllers\Controller;
use Essa\APIToolKit\Api\ApiResponse;
use App\Http\Requests\DisplayRequest;
use App\Http\Requests\Store\StoreChecklistRequest;
use App\Http\Resources\Store\StoreChecklistResource;
use App\Services\StoreServices\StoreChecklistService;

class StoreChecklistController extends Controller
{
    use ApiResponse;

    protected $storeChecklistService;

    public function __construct(StoreChecklistService $storeChecklistService)
    {
        $this->storeChecklistService = $storeChecklistService;
    }

    public function index(DisplayRequest $request)
    {
        $status = $request->input("status");
        $pagination = $request->pagination;

        $store_checklists = StoreChecklist::when(
            $status == "inactive",
            function ($query) {
                $query->onlyTrashed();
            }
        )
            ->with(
                "store",
                "checklist",
                "checklist.store_checklist.sections.questions.options"
            )
            ->useFilters()
            ->dynamicPaginate();

        if ($store_checklists->isEmpty()) {
            return $this->responseNotFound(__("messages.data_not_found"));
        }

        if (!$pagination) {
            StoreChecklistResource::collection($store_checklists);
        } else {
            $store_checklists = StoreChecklistResource::collection(
                $store_checklists
            );
        }

        return $this->responseSuccess(
            "Store Checklists retrieved successfully",
            $store_checklists
        );
    }

    public function show($id)
    {
        $store_checklist = StoreChecklist::withTrashed()
            ->with(
                "store",
                "checklist",
                "checklist.store_checklist.sections.questions.options"
            )
            ->find($id);

        if (!$store_checklist) {
            return $this->responseNotFound(__("messages.id_not_found"));
        }

        $transform_data = new StoreChecklistResource($store_checklist);

        return $this->responseSuccess(
            "Store Checklist retrieved successfully",
            $transform_data
        );
    }

    public function store(StoreChecklistRequest $request)
    {
        $store_checklist = $this->storeChecklistService->createStoreChecklist(
            $request->validated()
        );

        return $this->responseCreated(
            "Store Checklist successfully created",
            new StoreChecklistResource($store_checklist["store_checklist"])
        );
    }

    public function update(StoreChecklistRequest $request, $id)
    {
        $store_checklist = $this->storeChecklistService->updateStoreChecklist(
            $id,
            $request->validated()
        );

        return $this->responseSuccess(
            $store_checklist["message"],
            new StoreChecklistResource($store_checklist["store_checklist"])
        );
    }

    public function toggleArchived($id)
    {
        $store_checklist = $this->storeChecklistService->toggleArchived($id);

        if (!$store_checklist) {
            return $this->responseNotFound(__("messages.id_not_found"));
        }

        return $this->responseSuccess(
            $store_checklist["message"],
            $store_checklist["store_checklist"]
        );
    }
}
