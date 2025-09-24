<?php

namespace App\Services\StoreServices;

use Carbon\Carbon;
use App\Models\Store;

class StoreService
{
    public static function createStore(array $data): array
    {
        $store = Store::create([
            "name" => $data["name"],
            "area_id" => $data["area_id"],
            "region_id" => $data["region_id"],
        ]);

        return [
            "store" => $store,
        ];
    }

    public static function updateStore(int $id, array $data): array
    {
        $store = Store::find($id);

        if (!$store) {
            return [];
        }

        $store->name = $data["name"];
        $store->area_id = $data["area_id"];
        $store->region_id = $data["region_id"];

        if (!$store->isDirty()) {
            $message = "No Changes";
        } else {
            $store->save();
            $message = "Store successfully updated";
        }

        return ["message" => $message, "store" => $store];
    }

    public static function toggleArchived(int $id): array
    {
        $store = Store::withTrashed()->find($id);

        if (!$store) {
            return [];
        }

        if ($store->trashed()) {
            $store->restore();
            $message = "Store successfully restored";
        } else {
            $store->delete();
            $message = "Store successfully archived";
        }

        return ["message" => $message, "store" => $store];
    }
}
