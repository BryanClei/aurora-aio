<?php

namespace App\Filters;

use Essa\APIToolKit\Filters\QueryFilters;

class OneRdfUserFilters extends QueryFilters
{
    protected array $allowedFilters = [];

    protected array $columnSearch = [];

    public function status($status)
    {
        return $this->builder->when($status == "inactive", function ($query) {
            $query->onlyTrashed();
        });
    }

    public function syncing($syncing)
    {
        return $this->builder->when($syncing == "for_syncing", function ($query) {
            $query->whereNull("synced_at");
        })->when($syncing == "synced", function ($query) {
            $query->whereNotNull("synced_at");
        });
    }
}
