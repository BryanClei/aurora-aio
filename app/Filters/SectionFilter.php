<?php

namespace App\Filters;

use Essa\APIToolKit\Filters\QueryFilters;

class SectionFilter extends QueryFilters
{
    protected array $allowedFilters = [];

    protected array $columnSearch = [];
}
