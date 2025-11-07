<?php

namespace App\Http\Controllers\Api\Report\Export;

use Carbon\Carbon;
use App\Models\Area;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\Reports\StoreAreaPerWeekExport;
use App\Helpers\FourWeekCalendarHelper;
use App\Exports\Reports\StoreGradesExport;
use App\Http\Resources\Area\QAAreaResource;

class ExportStoreChecklistController extends Controller
{
    public function storeGradesExport(Request $request)
    {
        $date = $request->input("date")
            ? Carbon::parse($request->input("date"), config("app.timezone"))
            : Carbon::today(config("app.timezone"));

        $calendar = (object) FourWeekCalendarHelper::getMonthBasedFourWeek(
            $date
        );

        $area_id = $request->area_id;

        $area = Area::with([
            "store.store_checklist.weekly_record" => function ($query) use (
                $calendar
            ) {
                $query
                    ->where("month", $calendar->month)
                    ->where("year", $calendar->year);
            },
        ])
            ->whereHas("store")
            ->where("id", $area_id)
            ->first();

        return Excel::download(
            new StoreGradesExport($calendar, $area),
            "store_checklist.xlsx"
        );
    }

    public function storeAreaPerWeekExport(Request $request)
    {
        $area_id = $request->area_id;
        $week = $request->week;
        $month = $request->month;
        $year = $request->year;

        $area = Area::with([
            "store.store_checklist.weekly_record" => function ($query) use (
                $week,
                $month,
                $year
            ) {
                $query
                    ->where("week", $week)
                    ->where("month", $month)
                    ->where("year", $year);
            },
            "store.store_checklist.weekly_record.audit_trail",
        ])
            ->whereHas("store", function ($query) use ($week, $month, $year) {
                $query->whereHas("store_checklist.weekly_record", function (
                    $query
                ) use ($week, $month, $year) {
                    $query
                        ->where("week", $week)
                        ->where("month", $month)
                        ->where("year", $year);
                });
            })
            ->where("id", $area_id)
            ->first();

        if (!$area) {
            return response()->json(["message" => "Area not found"], 404);
        }

        $monthName = date("F", mktime(0, 0, 0, $month, 1));
        $filename = "Area_{$area->name}_Week{$week}_{$monthName}_{$year}.xlsx";

        return Excel::download(
            new StoreAreaPerWeekExport($area, $week, $month, $year),
            $filename
        );
    }
}
