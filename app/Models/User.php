<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Filters\UserFilter;
use Laravel\Sanctum\HasApiTokens;
use Essa\APIToolKit\Filters\Filterable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens, SoftDeletes, Filterable;

    protected string $default_filters = UserFilter::class;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        "id_prefix",
        "id_no",
        "first_name",
        "middle_name",
        "last_name",
        "suffix",
        "mobile_number",
        "gender",
        "one_charging_id",
        "one_charging_sync_id",
        "one_charging_code",
        "one_charging_name",
        "username",
        "password",
        "role_id",
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = ["password", "remember_token"];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            "email_verified_at" => "datetime",
            "password" => "hashed",
        ];
    }

    public function one_charging()
    {
        return $this->belongsTo(
            OneCharging::class,
            "one_charging_sync_id",
            "sync_id"
        )->withTrashed();
    }

    public function role()
    {
        return $this->belongsTo(Role::class, "role_id", "id")->withTrashed();
    }
}
