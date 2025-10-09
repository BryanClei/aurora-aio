<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class StoreChecklistDuty extends Model
{
    use SoftDeletes, HasFactory;

    protected $table = "store_checklist_staff_duties";

    protected $fillable = [
        "store_checklist_weekly_records_id",
        "store_checklist_id",
        "staff_id",
        "staff_name",
    ];

    public function store_checklist()
    {
        return $this->belongsTo(
            StoreChecklist::class,
            "store_checklist_id",
            "id"
        );
    }

    public function weekly_records()
    {
        return $this->belongsTo(
            StoreChecklistWeeklyRecord::class,
            "store_checklist_weekly_records_id",
            "id"
        );
    }
}
