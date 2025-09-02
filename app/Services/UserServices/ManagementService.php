<?php

namespace App\Services\UserServices;

use App\Models\User;
use App\Models\OneCharging;

class ManagementService
{
    public function createUser(array $data): User
    {
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
            "password" => bcrypt($data["username"]),
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
