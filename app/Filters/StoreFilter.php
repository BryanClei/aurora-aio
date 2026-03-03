<?php

namespace App\Filters;

use Essa\APIToolKit\Filters\QueryFilters;

class StoreFilter extends QueryFilters
{
    protected array $allowedFilters = [];

    protected array $columnSearch = ["name"];

    public function region($region)
    {
        return $this->builder->where("region_id", $region);
    }

    public function area($area)
    {
        return $this->builder->where("area_id", $area);
    }
}
