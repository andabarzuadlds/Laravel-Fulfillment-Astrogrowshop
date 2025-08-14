<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\ProductCostsController;

class ProductCostsColeCron extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'costs_cole:cron';

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
    public function handle()
    {
        
        \Log::info("Costs Cole Cron is Running ... !");
        
       $costs =  new ProductCostsController;
       $costs->add_products_to_sync_cole();
        
        return Command::SUCCESS;
    }
}
