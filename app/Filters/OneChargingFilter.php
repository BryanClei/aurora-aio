<?php

namespace App\Filters;

use Essa\APIToolKit\Filters\QueryFilters;

class OneChargingFilter extends QueryFilters
{
    protected array $allowedFilters = [];

    protected array $columnSearch = ["name"];
}
