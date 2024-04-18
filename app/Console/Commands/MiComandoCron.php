<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class MiComandoCron extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:name';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     *
     * @return int
     */

     /*protected $signature = 'backup:database';  // Ejemplo de comando*/
    public function handle()
    {
        Artisan::call(env('MODEL_BAKUP', 'backup:run --only-db')); 

        // Puedes agregar un mensaje de éxito opcional 
        $this->info('Database backup completed successfully!');  

        return Command::SUCCESS;       
    }

    
}
