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

class DatabaseSchemaDumpCommand extends Command
{
    use MakesProcesses,
        ValidatesDatabaseDrivers;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:schema:dump {--filename=schema.sql} {--driver=mysql}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dumps a database schema to file, optionally using provided filename and driver (Laravel v8.0 PR)';

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

        if (file_exists($filename)) {
            $this->info('The specified file already exists, exiting.');

            return;
        }

        if (!$this->dump($filename, $driver)) {
            $this->info('Failed to dump the database schema.');

            return;
        }

        $this->info('Finished.');
    }

    /**
     * Dump and process the database schema.
     *
     * @param string $filename
     * @param string $driver
     * @return bool
     */
    protected function dump(string $filename, string $driver): bool
    {
        try {
            $this->createSchemaDump($driver, $filename);
            $this->removeAutoIncrementingState($driver, $filename);
            $this->appendMigrationData($filename);
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }

    /**
     * Base dump command for the specified database driver.
     *
     * @param string $driver
     * @return string
     * @throws \Permafrost\LaravelDumpSchema\Exceptions\UnsupportedDatabaseDriverException
     */
    protected function baseDumpCommand(string $driver): string
    {
        $DB_HOST = env('DB_HOST');
        $DB_USER = env('DB_USERNAME');
        $DB_PASS = env('DB_PASSWORD');
        $DB_PORT = env('DB_PORT');
        $DB_NAME = env('DB_DATABASE');

        switch($driver) {
            case 'mysql':
                return 'mysqldump --set-gtid-purged=OFF --skip-add-drop-table --skip-add-locks --skip-comments --skip-set-charset --tz-utc '.
                    "--host=$DB_HOST --port=$DB_PORT --user=$DB_USER --password=$DB_PASS $DB_NAME";

            case 'postgres':
            default:
                throw new UnsupportedDatabaseDriverException('Unsupported database driver.');
        }
    }

    /**
     * Append the migration data to the schema dump.
     *
     * @param string $driver
     * @param string $filename
     * @throws \Exception
     */
    protected function appendMigrationData(string $driver, string $filename): void
    {
        with($process = $this->makeProcess(
            $this->baseDumpCommand($driver).' --tables migrations --no-create-info --skip-routines --compact'
        ))->mustRun(null);

        $process->wait();

        $content = file_get_contents($filename);
        $output = str_replace(
            ['),(', ' VALUES ('],
            ["),\n    (", " VALUES \n    ("],
            $process->getOutput()
        );

        file_put_contents($filename, $content.PHP_EOL.$output);
    }

    /**
     * Dumps the database schema to a file.
     * @param string $driver
     * @param string $filename
     * @throws \Exception
     */
    protected function createSchemaDump(string $driver, string $filename): void
    {
        with($process = $this->makeProcess(
            $this->baseDumpCommand($driver)." --routines --result-file=$filename --no-data"
        ))->mustRun(null);

        $process->wait();
    }

    /**
     * Remove the auto-incrementing state from the given schema dump.
     *
     * @param string $filename
     */
    protected function removeAutoIncrementingState(string $filename): void
    {
        $content = file_get_contents($filename);
        file_put_contents($filename, preg_replace(
            '/\s+AUTO_INCREMENT=[0-9]+/iu',
            '',
            $content
        ));
    }
}
