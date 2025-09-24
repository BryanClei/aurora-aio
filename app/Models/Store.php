<?php

namespace App\Models;

use App\Helpers\CodeHelper;
use App\Filters\StoreFilter;
use Essa\APIToolKit\Filters\Filterable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Store extends Model
{
    use HasFactory, Filterable, SoftDeletes;

    protected string $default_filters = StoreFilter::class;

    protected $table = "stores";

    protected $fillable = ["code", "name", "region_id", "area_id"];

    public function regions()
    {
        return $this->belongsTo(Region::class, "region_id", "id");
    }

    public function areas()
    {
        return $this->belongsTo(Area::class, "area_id", "id");
    }

    protected static function booted()
    {
        static::creating(function ($store) {
            if (empty($store->code)) {
                $store->code = CodeHelper::generateNumericCode(4);
            }
        });
    }
}
