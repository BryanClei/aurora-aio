<?php

namespace App\Services\AllowableServices;

use App\Models\AllowableDays;

class AllowableService
{
    public function getData()
    {
        $allowable = AllowableDays::first();

        return $allowable;
    }

    public static function create($data)
    {
        $allowable = AllowableDays::create([
            "allowable_days" => $data["days"],
        ]);

        return $allowable;
    }

    public function update($id, $data)
    {
        $allowable = AllowableDays::find($id);

        $allowable->update([
            "allowable_days" => $data["days"],
        ]);

        return $allowable;
    }
}
