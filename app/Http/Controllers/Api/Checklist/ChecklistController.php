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

class ChecklistController extends Controller
{
    use ApiResponse;

    public function index(DisplayRequest $request)
    {
        $status = $request->status;
        $pagination = $request->pagination;

        $checklist = Checklist::when($status == "inactive", function ($query) {
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
        $checklist = Checklist::find($id);

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
        $checklist = Checklist::create([
            "name" => $request->name,
        ]);

        return $this->responseCreated(
            "Checklist successfully created.",
            $checklist
        );
    }

    public function update(ChecklistRequest $request, $id)
    {
        $checklist = Checklist::find($id);

        if (!$checklist) {
            return $this->responseUnprocessable(
                "",
                __("messages.id_not_found")
            );
        }

        $checklist->name = $request->name;

        if (!$checklist->isDirty()) {
            return $this->responseSuccess("No Changes", $checklist);
        }

        $checklist->save();

        return $this->responseSuccess(
            "Checklist successfully updated",
            $checklist
        );
    }

    public function toggleArchive($id)
    {
        $checklist = Checklist::withTrashed()->find($id);

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

        if (Section::where("checklist_id", $checklist->id)->exists()) {
            return $this->responseUnprocessable(
                "",
                "Unable to archive. Checklist is currently in use."
            );
        }

        $checklist->delete();

        return $this->responseSuccess(
            __("messages.success_archived", ["attribute" => "Checklist"]),
            $checklist
        );
    }
}
