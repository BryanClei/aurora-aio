<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Category extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = "checklist_categories";

    protected $fillable = [
        "checklist_section_id",
        "name",
        "description",
        "status",
    ];
}
