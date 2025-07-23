<?php

namespace App\Filters;

use Essa\APIToolKit\Filters\QueryFilters;

class UserFilter extends QueryFilters
{
    protected array $allowedFilters = [];

    protected array $columnSearch = [];

    public function search($value)
    {
        $normalized = preg_replace("/\s+/", "-", $value);

        if (strpos($normalized, "-") !== false) {
            [$prefix, $id] = explode("-", $normalized, 2);

            $this->builder->where(function ($query) use ($prefix, $id) {
                $query->where("id_prefix", $prefix)->where("id_no", $id);
            });
        } else {
            // Fallback to partial match
            $this->builder->where(function ($query) use ($value) {
                $query
                    ->where("id_prefix", "like", "%{$value}%")
                    ->orWhere("id_no", "like", "%{$value}%");
            });
        }
    }
}
