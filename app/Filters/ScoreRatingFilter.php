<?php

namespace App\Filters;

use Essa\APIToolKit\Filters\QueryFilters;

class ScoreRatingFilter extends QueryFilters
{
    protected array $allowedFilters = [];

    protected array $columnSearch = ["rating", "score"];
}
