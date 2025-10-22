<?php

namespace App\Models;

use App\Filters\AutoSkippedFilter;
use Essa\APIToolKit\Filters\Filterable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AutoSkipped extends Model
{
    use HasFactory, SoftDeletes, Filterable;

    protected string $default_filters = AutoSkippedFilter::class;

    protected $table = "skipped_weekly_survey";

    protected $fillable = [
        "weekly_id",
        "week",
        "month",
        "year",
        "approver_id",
        "approver_name",
        "approved_at",
        "rejected_at",
    ];

    public function weeklyRecord()
    {
        return $this->belongsTo(
            StoreChecklistWeeklyRecord::class,
            "weekly_id",
            "id"
        );
    }
}
