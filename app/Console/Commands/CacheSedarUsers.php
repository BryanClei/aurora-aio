<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\CachedSedarUsersJob;

class CacheSedarUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = "cache:sedar-users";

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Command description";

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        CachedSedarUsersJob::dispatch();

        $this->info("Dispatched SEDAR cache job to queue.");
    }
}
