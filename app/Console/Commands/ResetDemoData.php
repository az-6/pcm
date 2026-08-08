<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

#[Signature('demo:reset {--force : Allow resetting outside local/testing environments}')]
#[Description('Rebuild the PCM database with deterministic fictional demo data')]
class ResetDemoData extends Command
{
    public function handle(): int
    {
        if (! app()->environment(['local', 'testing']) && ! $this->option('force')) {
            $this->components->error('Demo reset is restricted to local/testing environments. Use --force only for an isolated demo deployment.');

            return self::FAILURE;
        }

        if (app()->isProduction() && ! $this->confirm('This will erase and rebuild the configured database. Continue?', false)) {
            $this->components->warn('Demo reset cancelled.');

            return self::FAILURE;
        }

        $exitCode = Artisan::call('migrate:fresh', ['--seed' => true, '--force' => true]);
        $this->output->write(Artisan::output());

        if ($exitCode !== self::SUCCESS) {
            $this->components->error('Demo data reset failed.');

            return self::FAILURE;
        }

        $this->components->info('Fictional PCM demo data restored. All demo account passwords are "password".');

        return self::SUCCESS;
    }
}
