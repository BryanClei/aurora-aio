<?php

namespace App\Models;

use Essa\APIToolKit\Filters\Filterable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AuditTrail extends Model
{
    use HasFactory, Filterable;

    protected $casts = ["new_data", "previous_data"];

    protected $fillable = [
        "module_type",
        "module_name",
        "module_id",
        "action",
        "action_by",
        "action_by_name",
        "log_info",
        "previous_data",
        "new_data",
        "remarks",
        "ip_address",
        "user_agent",
    ];
}
