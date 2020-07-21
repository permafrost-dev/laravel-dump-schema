<?php

namespace Permafrost\LaravelDumpSchema\Traits;

trait ValidatesDatabaseDrivers
{
    /**
     * Checks if the provided database driver name is supported.
     *
     * @param string $driver
     * @return bool
     */
    protected function validateDatabaseDriverName(string $driver): bool
    {
        $validNames = [
            'mysql',
        ];

        return in_array($driver, $validNames);
    }
}
