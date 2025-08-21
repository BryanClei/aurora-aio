<?php

namespace App\Http\Controllers\Api\PatchNote;

use App\Models\PatchNote;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Essa\APIToolKit\Api\ApiResponse;
use App\Http\Requests\PatchNote\PatchNoteRequest;

class PatchNoteController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $status = $request->get("status", "published");

        $query = PatchNote::query();

        switch ($status) {
            case "published":
                $query
                    ->where("is_published", true)
                    ->whereNotNull("published_at");
                break;
            case "unpublished":
                $query->where("is_published", false);
                break;
            case "all":
                break;
            default:
                $query
                    ->where("is_published", true)
                    ->whereNotNull("published_at");
        }

        $patchNotes = $query
            ->latest("created_at")
            ->useFilters()
            ->dynamicPaginate();

        return $this->responseSuccess(
            "Patch notes display successfully.",
            $patchNotes
        );
    }

    public function show(PatchNote $patchNote)
    {
        if (!$patchNote) {
            return $this->responseNotFound("", "Patch note not found");
        }

        return $this->responseSuccess(
            "Patch note display successfully",
            $patchNote
        );
    }

    public function store(PatchNoteRequest $request)
    {
        $validated = $request->validated();

        if ($request->hasFile("file")) {
            $file = $request->file("file");
            $filename = $file->getClientOriginalName();
            $filepath = "storage/" . $file->store("patch-notes", "public");
        }

        $patchNote = PatchNote::create([
            "title" => $validated["title"],
            "description" => $validated["description"],
            "version" => $validated["version"] ?? null,
            "type" => $validated["type"],
            "filename" => $filename,
            "filepath" => $filepath,
            "is_published" => $validated["is_published"] ?? false,
            "published_at" =>
                $validated["is_published"] ?? false ? now() : null,
        ]);

        return $this->responseCreated(
            "Patch note successfully created.",
            $patchNote
        );
    }

    public function update(PatchNoteRequest $request, $id)
    {
        $validated = $request->validated();

        $patchNote = PatchNote::find($id);

        if (!$patchNote) {
            return $this->responseUnprocessable(
                "",
                __("messages.id_not_found")
            );
        }

        if ($request->hasFile("file")) {
            $file = $request->file("file");
            $filename = $file->getClientOriginalName();
            $filepath = "storage/" . $file->store("patch-notes", "public");

            $validated["filename"] = $filename;
            $validated["filepath"] = $filepath;
        } else {
            unset($validated["filename"], $validated["filepath"]);
        }

        if (
            isset($validated["is_published"]) &&
            $validated["is_published"] &&
            !$patchNote->published_at
        ) {
            $validated["published_at"] = now();
        }

        $patchNote->fill($validated);

        $dirty = collect($patchNote->getDirty())->except("updated_at");

        if ($dirty->isEmpty()) {
            return $this->responseSuccess("No changes detected.", $patchNote);
        }

        $patchNote->save();

        return $this->responseSuccess(
            "Patch note successfully updated.",
            $patchNote
        );
    }
}
