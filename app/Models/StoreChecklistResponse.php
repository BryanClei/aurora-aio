<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class StoreChecklistResponse extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = "checklist_response_answers";

    protected $fillable = [
        "response_id",
        "section_id",
        "section_title",
        "section_score",
        "question_id",
        "question_name",
        "answer_text",
        "selected_options",
        "store_visit",
        "expired",
        "condemned",
        "store_duty_id",
        "good_points",
        "notes",
        "score",
    ];

    public function sections()
    {
        return $this->belongsTo(Section::class, "section_id", "id");
    }
}
