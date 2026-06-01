<?php

namespace App\Console\Commands;

use App\Services\SystemHealthService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class MonitorSystemCommand extends Command
{
    protected $signature = 'tesnet:monitor';

    protected $description = 'Run health checks and log warnings for ops monitoring';

    public function handle(SystemHealthService $health): int
    {
        $issues = [];

        foreach ($health->checks() as $check) {
            if (in_array($check['status'], ['fail', 'warn'], true)) {
                $issues[] = "[{$check['status']}] {$check['label']}: {$check['detail']}";
            }
        }

        if ($issues === []) {
            $this->info('All checks OK.');
            Log::info('tesnet:monitor OK');

            return self::SUCCESS;
        }

        foreach ($issues as $line) {
            $this->warn($line);
            Log::warning('tesnet:monitor '.$line);
        }

        return $health->hasFailures() ? self::FAILURE : self::SUCCESS;
    }
}
