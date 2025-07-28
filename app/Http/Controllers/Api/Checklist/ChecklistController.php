<?php

namespace App\Http\Controllers\Api\Checklist;

use App\Models\Checklist;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class ChecklistController extends Controller
{
    public function index(DisplayRequest $request)
    {
        $status = $request->status;
        $pagination = $request->pagination;

        $store = Checklist::when($status == "inactive", function ($query) {
            $query->onlyTrashed();
        })
            ->useFilters()
            ->dynamicPaginate();
    }

    public function show()
    {
    }

    public function update()
    {
    }

    public function toggleArchived()
    {
    }
}
