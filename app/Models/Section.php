<?php

namespace App\Models;

use App\Filters\SectionFilter;
use Essa\APIToolKit\Filters\Filterable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Section extends Model
{
    use HasFactory, SoftDeletes, Filterable;

    protected string $default_filters = SectionFilter::class;

    protected $table = "sections";

    protected $fillable = [
        "checklist_id",
        "title",
        "description",
        "percentage",
        "order",
    ];

    public function checklist()
    {
        return $this->belongsTo(Checklist::class, "checklist_id", "id");
    }
}
