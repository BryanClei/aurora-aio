<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use App\Models\StoreChecklist;
use App\Services\Helper\AutoSkipHelper;

class AutoSkipBackMonth extends Command
{
    protected $signature = "auto-skip:backmonth";
    protected $description = "Create auto-skipped weekly records for missing weeks in the previous month (ACTIVE checklists only)";

    public function handle(): int
    {
        $today = Carbon::now("Asia/Manila");
        $target = $today->copy()->subMonthNoOverflow();
        $month = (int) $target->month;
        $year = (int) $target->year;

        $processed = 0;
        $eligible = 0; // checklist existed during backmonth
        $backfilled = 0; // created_weekly_count > 0
        $weeklyCreated = 0; // total weekly records created
        $blockedNoApprover = 0; // helper returned "No approver..."
        $skippedNewChecklist = 0; // helper returned "Checklist created after backmonth..."

        StoreChecklist::query()
            ->where("status", "active")
            ->select(["id"])
            ->chunkById(200, function ($checklists) use (
                &$processed,
                &$eligible,
                &$backfilled,
                &$weeklyCreated,
                &$blockedNoApprover,
                &$skippedNewChecklist
            ) {
                foreach ($checklists as $checklist) {
                    $result = AutoSkipHelper::autoSkipBackMonth([
                        "store_checklist_id" => $checklist->id,
                    ]);

                    $processed++;

                    // If helper returns string = skipped/blocked reason
                    if (is_string($result)) {
                        if (str_contains($result, "created after backmonth")) {
                            $skippedNewChecklist++;
                        } elseif (str_contains($result, "No approver found")) {
                            $blockedNoApprover++;
                        }
                        continue;
                    }

                    // If helper returns array = eligible (it ran checks and returned counts)
                    if (is_array($result)) {
                        $eligible++;

                        $created = (int) ($result["created_weekly_count"] ?? 0);
                        $weeklyCreated += $created;

                        if ($created > 0) {
                            $backfilled++;
                        }
                    }
                }
            });

        $this->info("Auto-skip backmonth summary for {$month}/{$year}:");
        $this->info("- Active checklists processed: {$processed}");
        $this->info("- Eligible (existed last month): {$eligible}");
        $this->info("- Backfilled checklists (created > 0): {$backfilled}");
        $this->info("- Weekly records created: {$weeklyCreated}");
        $this->info("- Skipped (new checklist): {$skippedNewChecklist}");
        $this->info("- Blocked (no approver): {$blockedNoApprover}");

        return Command::SUCCESS;
    }
}
