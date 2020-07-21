<?php
/**
 * This implements the dumping and loading of a database schema that will be used in Laravel v8.
 *
 * @see https://github.com/laravel/framework/pull/32275
 */

namespace Permafrost\LaravelDumpSchema;

use Illuminate\Console\Command;
use Permafrost\LaravelDumpSchema\Exceptions\UnsupportedDatabaseDriverException;
use Permafrost\LaravelDumpSchema\Traits\MakesProcesses;
use Permafrost\LaravelDumpSchema\Traits\ValidatesDatabaseDrivers;

class DatabaseSchemaLoadCommand extends Command
{
    use MakesProcesses,
        ValidatesDatabaseDrivers;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:schema:load {--filename=schema.sql} {--driver=mysql}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Loads a database schema dump file, optionally using provided filename and driver (Laravel v8.0 PR)';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $filename = database_path($this->option('filename') ?? 'schema.sql');
        $driver = strtolower($this->option('driver') ?? 'mysql');

        if (!$this->validateDatabaseDriverName($driver)) {
            $this->info('That database driver is not supported.');
            $this->info('Supported drivers: mysql');

            return;
        }

        if (!file_exists($filename)) {
            $this->info('The specified file does not exist.');

            return;
        }

        if (!$this->load($filename, $driver)) {
            $this->info('Not loading the specified schema dump file.');

            return;
        }

        $this->info('Finished.');
    }

    /**
     * Load the given schema file into the database.
     */
    public function load(string $filename, string $driver): bool
    {
        if (!$this->confirmLoad()) {
            return false;
        }

        $process = $this->makeProcess(
            $this->createLoadCommandForDriver($driver, $filename)
        );

        $process->mustRun(null);
        $process->wait();

        return true;
    }

    /**
     * Asks the user if they want to continue before loading the database schema file.
     */
    protected function confirmLoad(): bool
    {
        return $this->confirm('This command requires that the target database is empty (no existing tables).  Continue?');
    }

    /**
     * Creates the command to run for loading the schema file based on the database driver name.
     *
     * @throws \Permafrost\LaravelDumpSchema\Exceptions\UnsupportedDatabaseDriverException
     */
    protected function createLoadCommandForDriver(string $driver, string $filename): string
    {
        $DB_HOST = env('DB_HOST');
        $DB_USER = env('DB_USERNAME');
        $DB_PASS = env('DB_PASSWORD');
        $DB_PORT = env('DB_PORT');
        $DB_NAME = env('DB_DATABASE');

        switch ($driver) {
            case 'mysql':
                return "mysql --host=$DB_HOST --port=$DB_PORT --user=$DB_USER --password=$DB_PASS --database=$DB_NAME < $filename";

            case 'postgres':
            default:
                throw new UnsupportedDatabaseDriverException('Unsupported database driver.');
        }
    }
}
