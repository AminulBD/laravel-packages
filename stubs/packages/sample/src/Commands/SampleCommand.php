<?php

namespace YourDomain\Sample\Commands;

use Illuminate\Console\Command;

class SampleCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sample:sample';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'The sample command';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Hey, this is a sample command!');
    }
}
