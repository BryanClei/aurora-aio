<?php

namespace App\Console\Commands;

use Log;
use Carbon\Carbon;
use App\Models\StoreChecklist;
use Illuminate\Console\Command;
use App\Services\QAService\QAServices;
use App\Models\StoreChecklistWeeklyRecord;

class AutoSkipEndOfMonth extends Command
{
    protected $signature = "auto-skip:end-of-month";
    protected $description = "Automatically skip remaining weeks at the end of each month";
    protected $qaService;

    public function __construct(QAServices $qaService)
    {
        parent::__construct();
        $this->qaService = $qaService;
    }

    public function handle()
    {
        $today = Carbon::now("Asia/Manila");

        // Ensure this only runs on the last day of the month
        if (!$today->isLastOfMonth()) {
            $this->info("Today is not the last day of the month. Skipping...");
            return Command::SUCCESS;
        }

        $month = $today->month;
        $year = $today->year;
        $weeks = [1, 2, 3, 4];
        $skippedCount = 0;

        $storeChecklists = StoreChecklist::all();

        foreach ($storeChecklists as $checklist) {
            foreach ($weeks as $week) {
                // Check if a weekly record already exists for this week
                $existing = StoreChecklistWeeklyRecord::where(
                    "store_checklist_id",
                    $checklist->id
                )
                    ->where("month", $month)
                    ->where("year", $year)
                    ->where("week", $week)
                    ->first();

                if (!$existing) {
                    $data = [
                        "store_checklist_id" => $checklist->id,
                        "region_id" => $checklist->region_id ?? null,
                    ];

                    $this->qaService->autoSkip($data);
                    $skippedCount++;
                }
            }
        }

        $this->info(
            "Auto-skip completed. {$skippedCount} weekly records were skipped for month {$month}/{$year}."
        );

        Log::info(
            "Auto-skip end-of-month executed on {$today} — {$skippedCount} weeks skipped."
        );

        return Command::SUCCESS;
    }
}
