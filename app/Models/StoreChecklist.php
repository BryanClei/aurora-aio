<?php

namespace App\Models;

use App\Helpers\CodeHelper;
use App\Filters\StoreChecklistFilter;
use Essa\APIToolKit\Filters\Filterable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class StoreChecklist extends Model
{
    use HasFactory, Filterable, SoftDeletes;

    protected string $default_filters = StoreChecklistFilter::class;

    protected $table = "store_checklists";

    protected $fillable = ["code", "store_id", "checklist_id", "status"];

    public function store()
    {
        return $this->belongsTo(Store::class, "store_id", "id");
    }

    public function checklist()
    {
        return $this->belongsTo(Checklist::class, "checklist_id", "id");
    }

    public function sections()
    {
        return $this->hasMany(Section::class, "checklist_id", "id");
    }

    protected static function booted()
    {
        static::creating(function ($store_checklist) {
            if (empty($store_checklist->code)) {
                $store_checklist->code = CodeHelper::generateStoreCode();
            }
        });
    }
}
