<?php

namespace App\Models;

use App\Filters\OneRdfUserFilters;
use Essa\APIToolKit\Filters\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OneRdfUser extends Model
{
    use HasFactory, SoftDeletes, Filterable;

    protected string $default_filters = OneRdfUserFilters::class;

    protected $table = "one_rdf_users";

    protected $fillable = [
        "id_prefix",
        "id_no",
        "username",
        "password",
        "first_name",
        "middle_name",
        "last_name",
        "suffix",
        "synced_at",
    ];
}
