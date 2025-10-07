<?php

namespace App\Http\Controllers\Api\Checklist;

use App\Models\Section;
use App\Models\Checklist;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Essa\APIToolKit\Api\ApiResponse;
use App\Http\Requests\DisplayRequest;
use App\Http\Requests\Checklist\ChecklistRequest;
use App\Http\Resources\Checklist\ChecklistResource;
use App\Services\ChecklistServices\ChecklistService;

class ChecklistController extends Controller
{
    use ApiResponse;

    protected ChecklistService $checkListService;

    public function __construct(ChecklistService $checkListService)
    {
        $this->checkListService = $checkListService;
    }

    public function index(DisplayRequest $request)
    {
        $status = $request->status;
        $pagination = $request->pagination;

        $checklist = Checklist::with("sections.questions.options")
            ->when($status == "inactive", function ($query) {
                $query->onlyTrashed();
            })
            ->useFilters()
            ->dynamicPaginate();

        if ($checklist->isEmpty()) {
            return $this->responseNotFound(__("messages.data_not_found"));
        }

        if (!$pagination) {
            ChecklistResource::collection($checklist);
        } else {
            $checklist = ChecklistResource::collection($checklist);
        }

        return $this->responseSuccess(
            "Checklist display successfully",
            $checklist
        );
    }

    public function show($id)
    {
        $checklist = Checklist::with("sections.questions.options")->find($id);

        if (!$checklist) {
            return $this->responseNotFound(__("messages.id_not_found"));
        }

        return $this->responseSuccess(
            "Checklist display successfully.",
            $checklist
        );
    }

    public function store(ChecklistRequest $request)
    {
        $checklist = $this->checkListService->createChecklist($request->all());

        return $this->responseCreated(
            "Checklist successfully created.",
            $checklist["checklist"]
        );
    }

    public function update(ChecklistRequest $request, $id)
    {
        $result = $this->checkListService->updateChecklist(
            $id,
            $request->all()
        );

        if (!$result["has_changes"]) {
            return $this->responseSuccess(
                __("messages.no_changes"),
                $result["checklist"]
            );
        }

        return $this->responseSuccess(
            "Checklist updated successfully.",
            $result["checklist"]
        );
    }

    public function toggleArchive($id)
    {
        $checklist = Checklist::with("sections.questions.options")
            ->withTrashed()
            ->find($id);

        if (!$checklist) {
            return $this->responseUnprocessable(
                "",
                __("messages.id_not_found")
            );
        }

        if ($checklist->trashed()) {
            $checklist->restore();

            return $this->responseSuccess(
                __("messages.success_restored", ["attribute" => "Checklist"]),
                $checklist
            );
        }

        // if (Section::where("checklist_id", $checklist->id)->exists()) {
        //     return $this->responseUnprocessable(
        //         "",
        //         "Unable to archive. Checklist is currently in use."
        //     );
        // }

        $checklist->delete();

        return $this->responseSuccess(
            __("messages.success_archived", ["attribute" => "Checklist"]),
            $checklist
        );
    }
}
