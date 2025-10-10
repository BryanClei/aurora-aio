<?php

namespace App\Models;

use App\Filters\ScoreRatingFilter;
use Essa\APIToolKit\Filters\Filterable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ScoreRating extends Model
{
    use HasFactory, SoftDeletes, Filterable;

    protected string $default_filters = ScoreRatingFilter::class;

    protected $table = "score_rating";

    protected $fillable = ["rating", "score"];
}
