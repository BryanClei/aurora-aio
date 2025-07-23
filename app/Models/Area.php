<?php

namespace App\Models;

use App\Filters\AreaFilter;
use Laravel\Sanctum\HasApiTokens;
use Essa\APIToolKit\Filters\Filterable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Area extends Model
{
    use HasApiTokens, Filterable, SoftDeletes;

    protected string $default_filters = AreaFilter::class;

    protected $fillable = ["name", "region_id", "area_head_id"];

    public function region()
    {
        return $this->belongsTo(Region::class, "region_id", "id");
    }

    public function aread_head()
    {
        return $this->belongsTo(User::class, "area_head_id", "id");
    }
}
