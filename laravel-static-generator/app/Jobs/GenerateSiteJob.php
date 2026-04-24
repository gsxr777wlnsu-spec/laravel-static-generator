<?php

namespace App\Jobs;

use App\Contracts\HtmlGeneratorInterface;
use App\Models\Site;
use App\Models\User;
use App\Notifications\SiteGeneratedNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;

class GenerateSiteJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 1800; // 30 minutes limit

    public function __construct(
        public Site $site,
        public ?int $userId = null
    ) {}

    public function handle(HtmlGeneratorInterface $generator): void
    {
        $cacheKey = "site_generation_progress_{$this->site->id}";
        Cache::put($cacheKey, ['status' => 'processing', 'progress' => 0], now()->addHours(2));

        try {
            $result = $generator->generateSite($this->site, function ($current, $total) use ($cacheKey) {
                // Update cache with percentage
                $percentage = $total > 0 ? (int) floor(($current / $total) * 100) : 100;
                Cache::put($cacheKey, ['status' => 'processing', 'progress' => $percentage], now()->addHours(2));
            });

            Cache::put($cacheKey, [
                'status' => 'completed', 
                'progress' => 100,
                'result' => $result
            ], now()->addHours(24));

            if ($this->userId) {
                $user = User::find($this->userId);
                if ($user) {
                    $user->notify(new SiteGeneratedNotification($this->site, true, count($result['generated_files'] ?? [])));
                }
            }
        } catch (\Exception $e) {
            Cache::put($cacheKey, [
                'status' => 'failed',
                'progress' => 100,
                'error' => $e->getMessage()
            ], now()->addHours(24));

            if ($this->userId) {
                $user = User::find($this->userId);
                if ($user) {
                    $user->notify(new SiteGeneratedNotification($this->site, false, 0, $e->getMessage()));
                }
            }

            throw $e;
        }
    }
}
