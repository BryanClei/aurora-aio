<?php

namespace App\Models;

use Essa\APIToolKit\Filters\Filterable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Question extends Model
{
    use HasFactory, SoftDeletes, Filterable;

    protected $fillable = [
        "section_id",
        "question_text",
        "question_type",
        "options",
        "order_index",
    ];

    protected $table = "checklist_questions";

    protected $casts = [
        "options" => "array",
    ];

    public function section()
    {
        return $this->belongsTo(Section::class, "section_id", "id");
    }

    public function options()
    {
        return $this->hasMany(Option::class, "question_id", "id");
    }
}
