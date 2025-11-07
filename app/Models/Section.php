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

    protected $table = "checklist_sections";

    protected $fillable = [
        "checklist_id",
        "category_id",
        "title",
        "percentage",
        "order_index",
    ];

    public function checklist()
    {
        return $this->belongsTo(Checklist::class, "checklist_id", "id");
    }

    public function questions()
    {
        return $this->hasMany(Question::class, "section_id", "id");
    }

    public function category()
    {
        return $this->belongsTo(Category::class, "category_id", "id");
    }
}
