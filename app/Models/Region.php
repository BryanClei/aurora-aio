<?php

namespace App\Models;

use App\Filters\RegionFilter;
use Laravel\Sanctum\HasApiTokens;
use Essa\APIToolKit\Filters\Filterable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Region extends Model
{
    use HasApiTokens, Filterable, SoftDeletes;

    protected string $default_filters = RegionFilter::class;

    protected $fillable = ["name", "region_head_id"];

    public function region_head()
    {
        return $this->belongsTo(
            User::class,
            "region_head_id",
            "id"
        )->withTrashed();
    }

    public function area()
    {
        return $this->hasMany(Area::class, "region_id", "id");
    }
}
