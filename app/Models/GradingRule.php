<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class GradingRule extends Model
{
    use HasFactory;

    protected $table = "grading_rule";

    protected $fillable = ["cap_percentage"];
}
