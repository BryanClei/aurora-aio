<?php

namespace App\Filters;

use Essa\APIToolKit\Filters\QueryFilters;

class AreaFilter extends QueryFilters
{
    protected array $allowedFilters = [];

    protected array $columnSearch = ["name"];
}
