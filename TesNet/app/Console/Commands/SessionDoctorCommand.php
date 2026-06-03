<?php

namespace App\Console\Commands;

use App\Support\SessionConfig;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SessionDoctorCommand extends Command
{
    protected $signature = 'tesnet:session-doctor';

    protected $description = 'Diagnose 419 Page Expired / session cookie problems on the captive portal';

    public function handle(): int
    {
        $diag = SessionConfig::diagnostics();

        $this->info('Session / CSRF diagnostics');
        $this->table(
            ['Setting', 'Value'],
            [
                ['APP_URL', $diag['app_url']],
                ['Config cached', $diag['config_cached'] ? 'YES (run config:clear)' : 'no'],
                ['SESSION_DRIVER', $diag['session_driver']],
                ['session.secure (effective)', $diag['session_secure'] ? 'true' : 'false'],
                ['session.domain', $diag['session_domain'] ?? '(empty)'],
                ['session.same_site', $diag['session_same_site'] ?? '(none)'],
                ['SESSION_ENCRYPT', $diag['session_encrypt'] ? 'true' : 'false'],
                ['sessions table', $diag['sessions_table'] ? 'exists' : 'MISSING'],
            ]
        );

        if ($diag['issues'] !== []) {
            $this->warn('Issues found:');

            foreach ($diag['issues'] as $issue) {
                $this->line('  • '.$issue);
            }

            $this->newLine();
            $this->line('Recommended .env for http://192.168.88.2 captive portal:');
            $this->line('  APP_URL=http://192.168.88.2');
            $this->line('  APP_FORCE_HTTPS=false');
            $this->line('  SESSION_DRIVER=database');
            $this->line('  SESSION_SECURE_COOKIE=false');
            $this->line('  SESSION_ENCRYPT=false');
            $this->line('  # Leave SESSION_DOMAIN unset or empty — never use the IP as domain');
            $this->line('  TRUST_PROXIES=false');
            $this->newLine();
            $this->line('Then: php artisan config:clear && php artisan optimize:clear');

            return self::FAILURE;
        }

        if (config('session.driver') === 'database' && Schema::hasTable('sessions')) {
            try {
                DB::table('sessions')->limit(1)->get();
                $this->info('Database sessions table is readable.');
            } catch (\Throwable $exception) {
                $this->error('Cannot read sessions table: '.$exception->getMessage());

                return self::FAILURE;
            }
        }

        $this->info('No obvious session misconfiguration detected.');

        return self::SUCCESS;
    }
}
