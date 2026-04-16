<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use App\Models\StoreChecklist;
use App\Services\Helper\AutoSkipHelper;

class AutoSkipBackMonth extends Command
{
    protected $signature = "auto-skip:backmonth";
    protected $description = "Create auto-skipped weekly records for missing weeks in the past 12 months (ACTIVE checklists only)";

    public function handle(): int
    {
        $today = Carbon::now("Asia/Manila");

        for ($i = 12; $i >= 1; $i--) {
            $target = $today->copy()->subMonthsNoOverflow($i);
            $month  = (int) $target->month;
            $year   = (int) $target->year;

            $processed          = 0;
            $eligible           = 0;
            $backfilled         = 0;
            $weeklyCreated      = 0;
            $blockedNoApprover  = 0;
            $skippedNewChecklist = 0;

            StoreChecklist::query()
                ->where("status", "active")
                ->select(["id"])
                ->chunkById(200, function ($checklists) use (
                    $month,
                    $year,
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
                            "month"              => $month,
                            "year"               => $year,
                        ]);

                        $processed++;

                        if (is_string($result)) {
                            if (str_contains($result, "created after backmonth")) {
                                $skippedNewChecklist++;
                            } elseif (str_contains($result, "No approver found")) {
                                $blockedNoApprover++;
                            }
                            continue;
                        }

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

            $this->info("--- {$month}/{$year} ---");
            $this->info("- Active checklists processed: {$processed}");
            $this->info("- Eligible (existed last month): {$eligible}");
            $this->info("- Backfilled checklists (created > 0): {$backfilled}");
            $this->info("- Weekly records created: {$weeklyCreated}");
            $this->info("- Skipped (new checklist): {$skippedNewChecklist}");
            $this->info("- Blocked (no approver): {$blockedNoApprover}");
            $this->newLine();
        }

        $this->info("Done. Processed 12 months back from today.");

        return Command::SUCCESS;
    }
}
