<?php

namespace App\Models;

use Essa\APIToolKit\Filters\Filterable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Option extends Model
{
    use HasFactory, SoftDeletes, Filterable;

    protected $fillable = ["question_id", "option_text", "order_index"];

    protected $table = "checklist_question_options";

    public function question()
    {
        return $this->belongsTo(Question::class, "question_id", "id");
    }
}
