<?php

namespace App\Models;

use Essa\APIToolKit\Filters\Filterable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class StoreChecklist extends Model
{
    use HasFactory, Filterable, SoftDeletes;

    protected string $default_filters = StoreChecklistFilter::class;

    protected $table = "store_checklist";

    protected $fillable = ["code", "store_id", "checklist_id"];

    public function store()
    {
        return $this->belongsTo(Store::class, "store_id", "id");
    }

    public function checklist()
    {
        return $this->belongsTo(Checklist::class, "checklist_id", "id");
    }
}
