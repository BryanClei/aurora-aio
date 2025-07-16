<?php

namespace App\Models;

use App\Filters\OneChargingFilter;
use Essa\APIToolKit\Filters\Filterable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OneCharging extends Model
{
    use HasFactory, Filterable, SoftDeletes;

    protected string $default_filters = OneChargingFilter::class;

    protected $table = "one_charging";

    protected $fillable = [
        "code",
        "name",
        "company_id",
        "company_code",
        "company_name",
        "business_unit_id",
        "business_unit_code",
        "business_unit_name",
        "department_id",
        "department_code",
        "department_name",
        "department_unit_id",
        "department_unit_code",
        "department_unit_name",
        "sub_unit_id",
        "sub_unit_code",
        "sub_unit_name",
        "location_id",
        "location_code",
        "location_name",
        "deleted_at",
    ];
}
