<?php

namespace App\Services\UserServices;

use App\Models\OneCharging;
use App\Models\OneRdfUser;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;

class ManagementService
{
    public function createUser(array $data): User
    {
        $syncing = $data["for_syncing"];

        $date_today = Carbon::now("Asia/Manila");

        if ($syncing) {
            $id_prefix = $data["personal_info"]["id_prefix"];
            $id_no = $data["personal_info"]["id_no"];

            $one_user = OneRdfUser::where("id_prefix", $id_prefix)
                ->where("id_no", $id_no)
                ->first();

            $one_user->update([
                "synced_at" => $date_today,
            ]);

            $password = Hash::make($one_user->password);
        } else {
            $password = Hash::make($data["username"]);
        }

        $oneCharging = OneCharging::findOrFail(
            $data["personal_info"]["one_charging_id"]
        );

        return User::create([
            "id_prefix" => $data["personal_info"]["id_prefix"],
            "id_no" => $data["personal_info"]["id_no"],
            "first_name" => $data["personal_info"]["first_name"],
            "middle_name" => $data["personal_info"]["middle_name"],
            "last_name" => $data["personal_info"]["last_name"],
            "suffix" => $data["personal_info"]["suffix"],
            "mobile_number" => $data["personal_info"]["mobile_number"],
            "gender" => $data["personal_info"]["gender"],
            "one_charging_id" => $oneCharging->id,
            "one_charging_sync_id" => $oneCharging->sync_id,
            "one_charging_code" => $oneCharging->code,
            "one_charging_name" => $oneCharging->name,
            "username" => $data["username"],
            "password" => $password,
            "role_id" => $data["role_id"],
        ]);
    }

    public function updateUser(array $data, int $id): ?User
    {
        $user = User::find($id);

        if (!$user) {
            return null;
        }

        $oneCharging = OneCharging::findOrFail(
            $data["personal_info"]["one_charging_id"]
        );

        $updateData = [
            "mobile_number" => $data["personal_info"]["mobile_number"],
            "username" => $data["username"],
            "role_id" => $data["role_id"],
            "one_charging_id" => $oneCharging->id,
            "one_charging_sync_id" => $oneCharging->sync_id,
            "one_charging_code" => $oneCharging->code,
            "one_charging_name" => $oneCharging->name,
        ];

        $user->fill($updateData);

        if ($user->isDirty()) {
            $user->save();
        }

        return $user;
    }

    public function toggleArchived(int $id): ?array
    {
        $user = User::withTrashed()->find($id);

        if (!$user) {
            return null;
        }

        if ($user->id == auth()->id || $user->role_id == 1) {
            return [
                "message" => __("messages.cannot_archive_own_account"),
                "user" => [],
            ];
        }

        if ($user->trashed()) {
            $user->restore();
            $message = __("messages.success_restored", ["attribute" => "User"]);
        } else {
            $user->delete();
            $message = __("messages.success_archived", ["attribute" => "User"]);
        }

        return [
            "message" => $message,
            "user" => $user,
        ];
    }
}
