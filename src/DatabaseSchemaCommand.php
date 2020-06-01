<?php
/**
 * This implements the dumping and loading of a database schema that will be used in Laravel v8.
 *
 * @see https://github.com/laravel/framework/pull/32275
 */

namespace Permafrost\LaravelDS;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class DatabaseSchemaCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:schema {cmd}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dump or load the database schema (Laravel v8.0 PR)';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $outputFile = database_path('schema-dump.sql');
        $command = (string)$this->argument('cmd');

        if (!in_array($command, ['dump', 'load'])) {
            $this->info('Valid arguments are "dump" and "load".');

            return;
        }

        if (!$this->$command($outputFile)) {
            return;
        }

        $this->info('Finished.');
    }

    /**
     * Dump and process the database schema.
     *
     * @param $filename
     */
    protected function dump($filename): bool
    {
        $this->createSchemaDump($filename);
        $this->removeAutoIncrementingState($filename);
        $this->appendMigrationData($filename);

        return true;
    }

    /**
     * Load the given schema file into the database.
     *
     * @param string $path
     */
    public function load($filename): bool
    {
        $DB_HOST = env('DB_HOST');
        $DB_USER = env('DB_USERNAME');
        $DB_PASS = env('DB_PASSWORD');
        $DB_PORT = env('DB_PORT');
        $DB_NAME = env('DB_DATABASE');

        if (!$this->confirm('This command requires that the target database is empty (no existing tables).  Continue?')) {
            return false;
        }

        $process = $this->makeProcess("mysql --host=$DB_HOST --port=$DB_PORT --user=$DB_USER --password=$DB_PASS --database=$DB_NAME < $filename");

        $process->mustRun(null);
        $process->wait();

        return true;
    }

    /**
     * Create a new process instance.
     *
     * @param array $arguments
     */
    protected function makeProcess(...$arguments): Process
    {
        return Process::fromShellCommandline(...$arguments);
    }

    /**
     * Base dump command for MySQL.
     */
    protected function baseDumpCommand(): string
    {
        $DB_HOST = env('DB_HOST');
        $DB_USER = env('DB_USERNAME');
        $DB_PASS = env('DB_PASSWORD');
        $DB_PORT = env('DB_PORT');
        $DB_NAME = env('DB_DATABASE');

        return 'mysqldump --set-gtid-purged=OFF --skip-add-drop-table --skip-add-locks --skip-comments --skip-set-charset --tz-utc '.
            "--host=$DB_HOST --port=$DB_PORT --user=$DB_USER --password=$DB_PASS $DB_NAME";
    }

    /**
     * Append the migration data to the schema dump.
     *
     * @param string $path
     */
    protected function appendMigrationData(string $filename): void
    {
        with($process = $this->makeProcess(
            $this->baseDumpCommand().' --tables migrations --no-create-info --skip-routines --compact'
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
     */
    protected function createSchemaDump(string $filename): void
    {
        with($process = $this->makeProcess(
            $this->baseDumpCommand()." --routines --result-file=$filename --no-data"
        ))->mustRun(null);

        $process->wait();
    }

    /**
     * Remove the auto-incrementing state from the given schema dump.
     *
     * @param string $path
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
