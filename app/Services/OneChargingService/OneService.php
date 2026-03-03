<?php

namespace App\Services\OneChargingService;

use App\Models\OneCharging;
use App\Models\OneRdfUser;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;

class OneService
{
    public function sync(): array
    {
        $url = "https://api-one.rdfmis.com/api/charging_api?pagination=none";
        $apiKey = "hello world!";

        $response = Http::withHeaders([
            "API_KEY" => $apiKey,
        ])->get($url);

        if ($response->failed()) {
            return [
                "success" => false,
                "message" => "Failed to fetch charging data",
                "data" => [],
            ];
        }

        $data = $response->json("data");

        $sync = collect($data)->map(function ($charging) {
            return [
                "sync_id" => (int) $charging["id"],
                "code" => $charging["code"],
                "name" => $charging["name"],
                "company_id" => $charging["company_id"],
                "company_code" => $charging["company_code"],
                "company_name" => $charging["company_name"],
                "business_unit_id" => $charging["business_unit_id"],
                "business_unit_code" => $charging["business_unit_code"],
                "business_unit_name" => $charging["business_unit_name"],
                "department_id" => $charging["department_id"],
                "department_code" => $charging["department_code"],
                "department_name" => $charging["department_name"],
                "department_unit_id" => $charging["unit_id"],
                "department_unit_code" => $charging["unit_code"],
                "department_unit_name" => $charging["unit_name"],
                "sub_unit_id" => $charging["sub_unit_id"],
                "sub_unit_code" => $charging["sub_unit_code"],
                "sub_unit_name" => $charging["sub_unit_name"],
                "location_id" => $charging["location_id"],
                "location_code" => $charging["location_code"],
                "location_name" => $charging["location_name"],
                "deleted_at" => $charging["deleted_at"]
                    ? Carbon::parse($charging["deleted_at"])->format(
                        "Y-m-d H:i:s"
                    )
                    : null,
            ];
        });

        $existingSyncIds = OneCharging::withTrashed()
            ->pluck("sync_id")
            ->toArray();

        $newRecords = $sync->filter(
            fn($item) => !in_array($item["sync_id"], $existingSyncIds, true)
        );

        $updatedRecords = $sync->filter(
            fn($item) => in_array($item["sync_id"], $existingSyncIds, true)
        );

        OneCharging::upsert(
            $sync->toArray(),
            ["sync_id"],
            [
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
            ]
        );

        return [
            "success" => true,
            "message" => "One charging sync successfully.",
            "data" => [
                "synced_count" => $sync->count(),
                "success_sync" => $newRecords->count(),
                "sync_updated" => $updatedRecords->count(),
            ],
        ];
    }

    public function userSync($data)
    {
        $filteredData = collect($data)->only([
            "id_prefix",
            "id_no",
            "username",
            "first_name",
            "middle_name",
            "last_name",
            "suffix",
            "password",
        ])->toArray();

        $employeeId = $filteredData["id_prefix"] . "-" . $filteredData["id_no"];

        $user = User::where("id_prefix", $filteredData["id_prefix"])
            ->where("id_no", $filteredData["id_no"])
            ->first();

        $oneRdfUser = OneRdfUser::where("id_prefix", $filteredData["id_prefix"])
            ->where("id_no", $filteredData["id_no"])
            ->first();

        if ($user) {
            $user->fill($filteredData);

            if ($user->isDirty()) {
                $user->save();
                return [
                    "updated" => true,
                    "data" => $user,
                    "changes" => $user->getDirty(),
                ];
            }

            return [
                "updated" => false,
                "data" => $user,
            ];
        }

        if ($oneRdfUser) {
            $oneRdfUser->fill($filteredData);

            if ($oneRdfUser->isDirty()) {
                $oneRdfUser->save();
                return [
                    "updated" => true,
                    "data" => $oneRdfUser,
                    "changes" => $oneRdfUser->getDirty(),
                ];
            }

            return [
                "updated" => false,
                "data" => $oneRdfUser,
            ];
        }

        $newUser = OneRdfUser::create($filteredData);

        return [
            "created" => true,
            "data" => $newUser,
        ];
    }

    public function oneRdfUserIndex($data)
    {
        $one_user = OneRdfUser::useFilters()
            ->dynamicPaginate();

        if ($one_user->isEmpty()) {
            return null;
        }

        return $one_user;
    }

    public function oneRdfUserShow($id)
    {
        $one_user = OneRdfUser::where("id", $id)
            ->first();

        if (!$one_user) {
            return null;
        }

        return $one_user;
    }

    public function changePassword($data, $id)
    {
        [$prefix, $number] = explode("-", $id);

        $user = User::where("id_prefix", $prefix)
            ->where("id_no", $number)
            ->first();

        if (!$user) {
            return null;
        }

        $user->update([
            "password" => Hash::make($data["password"]),
        ]);

        return $user;
    }

    public function resetPassword($id)
    {
        [$prefix, $number] = explode("-", $id);

        $user = User::where("id_prefix", $prefix)
            ->where("id_no", $number)
            ->first();

        if (!$user) {
            return null;
        }

        $user->update([
            "password" => Hash::make($user->username),
        ]);

        return $user;
    }
}
