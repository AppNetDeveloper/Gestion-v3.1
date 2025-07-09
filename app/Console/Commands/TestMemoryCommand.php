<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class TestMemoryCommand extends Command
{
    protected $signature = 'test:memory';
    protected $description = 'Tests base Laravel command memory usage.';

    public function handle()
    {
        $this->info("Test command started.");
        $this->info("Memory usage: " . round(memory_get_usage() / 1024 / 1024, 2) . " MB");
        $this->info("Peak memory usage: " . round(memory_get_peak_usage() / 1024 / 1024, 2) . " MB");
        return Command::SUCCESS;
    }
}