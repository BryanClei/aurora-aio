<?php

namespace App\Models;

use App\Models\Checklist;
use Illuminate\Database\Eloquent\Model;

class Checklist extends Model
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
