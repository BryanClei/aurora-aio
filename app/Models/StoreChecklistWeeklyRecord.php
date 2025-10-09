<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class StoreChecklistWeeklyRecord extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        "store_checklist_id",
        "week",
        "month",
        "year",
        "weekly_grade",
        "is_auto_grade",
        "grade_source",
        "graded_by",
        "grade_notes",
    ];

    protected $casts = [
        "weekly_grade" => "decimal:2",
        "is_auto_grade" => "boolean",
    ];

    public function storeChecklist()
    {
        return $this->belongsTo(
            StoreChecklist::class,
            "store_checklist_id",
            "id"
        );
    }

    // Get period display
    public function getPeriodDisplayAttribute(): string
    {
        return "Week {$this->week}, " .
            Carbon::create($this->year, $this->month)->format("F Y");
    }

    // Get week date range
    public function getWeekRangeAttribute(): array
    {
        $date = Carbon::now()->setISODate($this->year, $this->week);
        return [
            "start" => $date->startOfWeek()->format("Y-m-d"),
            "end" => $date->endOfWeek()->format("Y-m-d"),
        ];
    }
}
