<?php

namespace App\Console\Commands;

use App\Services\CompanySyncService;
use Illuminate\Console\Command;

class SyncCompaniesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'idea:sync-companies';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync companies from IDEA API to database';

    /**
     * Execute the console command.
     */
    public function handle(CompanySyncService $syncService): int
    {
        $this->info('Starting company sync from IDEA API...');

        try {
            $result = $syncService->sync();

            $this->info("Sync completed successfully!");
            $this->line("Companies created: {$result['created']}");
            $this->line("Companies updated: {$result['updated']}");

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Sync failed: {$e->getMessage()}");

            return Command::FAILURE;
        }
    }
}
