<?php

namespace App\Models;

use App\Filters\StoreFilter;
use Essa\APIToolKit\Filters\Filterable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Store extends Model
{
    use HasFactory, Filterable, SoftDeletes;

    protected string $default_filters = StoreFilter::class;

    protected $table = "stores";

    protected $fillable = ["code"];
}
