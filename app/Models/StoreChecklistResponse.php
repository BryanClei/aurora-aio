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
        "weekly_record_id",
        "section_id",
        "section_title",
        "section_score",
        "section_order_index",
        "question_id",
        "question_text",
        "question_type",
        "question_order_index",
        "answer_text",
        "selected_options",
        "store_visit",
        "expired",
        "condemned",
        "store_duty_id",
        "good_points",
        "notes",
        "score",
        "attachment",
    ];

    public function sections()
    {
        return $this->belongsTo(Section::class, "section_id", "id");
    }

    public function staff_on_duty()
    {
        return $this->belongsTo(
            StoreChecklistDuty::class,
            "store_duty_id",
            "id"
        );
    }
}
