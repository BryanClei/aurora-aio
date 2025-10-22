<?php

namespace App\Services\ApproverServices;

class AutoSkippedService
{
    public static function approvedFunction($data)
    {
        $data->update([
            "approved_at" => Carbon::now(),
            "rejected_at" => null,
        ]);

        return $data;
    }

    public static function rejectedFunction($data)
    {
        $data->update([
            "approved_at" => null,
            "rejected_at" => Carbon::now(),
        ]);

        return $data;
    }
}
