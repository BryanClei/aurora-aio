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

    protected $fillable = ["code", "region_id", "area_id", "checklist_id"];
}
