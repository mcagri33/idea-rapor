<?php

namespace App\Console\Commands;

use App\Models\BilancoImport;
use App\Models\Company;
use App\Services\Bilanco\BilancoImportService;
use Illuminate\Console\Command;

class ImportBilancoCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bilanco:import 
                            {--company_id= : Company ID}
                            {--file= : Excel file path}
                            {--donem= : Dönem (YYYY-MM-DD)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import bilanço from Excel file';

    /**
     * Execute the console command.
     */
    public function handle(BilancoImportService $importService): int
    {
        $companyId = $this->option('company_id');
        $filePath = $this->option('file');
        $donem = $this->option('donem');

        if (!$companyId || !$filePath || !$donem) {
            $this->error('Missing required parameters. Usage:');
            $this->line('php artisan bilanco:import --company_id=1 --file=/path/to/file.xlsx --donem=2024-01-31');
            return Command::FAILURE;
        }

        // Validate company
        $company = Company::find($companyId);
        if (!$company) {
            $this->error("Company with ID {$companyId} not found.");
            return Command::FAILURE;
        }

        // Validate file
        if (!file_exists($filePath)) {
            $this->error("File not found: {$filePath}");
            return Command::FAILURE;
        }

        // Validate date format
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $donem)) {
            $this->error("Invalid date format. Expected YYYY-MM-DD, got: {$donem}");
            return Command::FAILURE;
        }

        $this->info("Starting bilanço import for company: {$company->name}");
        $this->info("File: {$filePath}");
        $this->info("Dönem: {$donem}");

        try {
            // Create bilanco import record
            $bilancoImport = BilancoImport::create([
                'company_id' => $companyId,
                'donem' => $donem,
                'status' => 'pending',
            ]);

            // Import
            $result = $importService->import($bilancoImport, $filePath);

            $this->info("Import completed successfully!");
            $this->line("Total rows processed: {$result['total']}");
            $this->line("Rows imported: {$result['imported']}");

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Import failed: {$e->getMessage()}");
            return Command::FAILURE;
        }
    }
}
