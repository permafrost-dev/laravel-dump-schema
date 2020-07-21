<?php

namespace Permafrost\LaravelDumpSchema\Traits;

use Symfony\Component\Process\Process;

trait MakesProcesses
{
    /**
     * Create a new process instance.
     *
     * @param array $arguments
     * @return \Symfony\Component\Process\Process
     */
    protected function makeProcess(...$arguments): Process
    {
        return Process::fromShellCommandline(...$arguments);
    }

}
