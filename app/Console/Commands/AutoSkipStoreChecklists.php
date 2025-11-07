<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use App\Models\StoreChecklist;
use Illuminate\Console\Command;
use App\Services\QAService\QAServices;

class AutoSkipStoreChecklists extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = "auto-skip:store-checklists";

    /**
     * The console command description.
     */
    protected $description = "Automatically skip all store checklists every Sunday at 11:59:59 PM";

    protected $qaService;

    public function __construct(QAServices $qaService)
    {
        parent::__construct();
        $this->qaService = $qaService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $today = Carbon::now();

        // Only proceed if today is Sunday
        if (!$today->isSunday()) {
            $this->info("Today is not Sunday. Skipping auto-skip task.");
            return Command::SUCCESS;
        }

        $storeChecklists = StoreChecklist::all();

        $count = 0;
        foreach ($storeChecklists as $checklist) {
            $data = [
                "store_checklist_id" => $checklist->id,
                "region_id" => $checklist->region_id ?? null, // ensure region_id exists or adjust logic
            ];

            $result = $this->qaService->autoSkip($data);
            $count++;
        }

        $this->info("Auto-skip executed successfully for {$count} checklists.");
        return Command::SUCCESS;
    }
}
