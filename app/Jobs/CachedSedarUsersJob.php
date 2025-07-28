<?php

namespace App\Jobs;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class CachedSedarUsersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $response = Http::withToken(env("SEDAR_API_KEY"))
            ->timeout(6000)
            ->get(env("SEDAR_API_USERS"));

        if ($response->successful()) {
            $newData = $response->json();
            $cachedData = Cache::get("sedar_users");

            if ($cachedData !== $newData) {
                Cache::put("sedar_users", $newData);
            }
        }
    }
}
