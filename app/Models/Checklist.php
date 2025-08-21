<?php

namespace App\Models;

use App\Models\Checklist;
use App\Filters\ChecklistFilter;
use Essa\APIToolKit\Filters\Filterable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Checklist extends Model
{
    use HasFactory, SoftDeletes, Filterable;

    protected string $default_filters = ChecklistFilter::class;

    protected $fillable = ["name"];

    public function section()
    {
        return $this->hasMany(Section::class, "id", "checklist_id");
    }
}
