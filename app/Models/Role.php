<?php

namespace App\Models;

use Essa\APIToolKit\Filters\Filterable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Role extends Model
{
    use HasFactory, SoftDeletes, Filterable;
    protected $fillable = ["id", "name", "access_permission"];

    protected $hidden = ["updated_at"];

    // protected string $default_filters = RoleFilter::class;

    protected $casts = [
        "access_permission" => "json",
    ];
}
