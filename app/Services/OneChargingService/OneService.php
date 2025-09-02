<?php

namespace App\Services\OneChargingService;

use App\Models\OneCharging;
use Carbon\Carbon;
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
}
