<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\StockController;

class ProductCostsCron extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'product_costs:cron';

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
       $stock =  new StockController;
    //   $stock->sync_stock_task();
    //   $stock->check_products_tienda();
    
    $stock->sync_stock_test();

       
        return Command::SUCCESS;
    }
}
