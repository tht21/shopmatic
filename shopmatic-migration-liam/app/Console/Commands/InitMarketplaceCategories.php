<?php

namespace App\Console\Commands;

use App\Jobs\ImportCategories;
use Illuminate\Console\Command;

class InitMarketplaceCategories extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'init:mpcategories';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Runs the necessary scripts / jobs whenever a new deployment takes place';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }
    
    /**
     * Execute the console command.
     *
     * @return mixed
     * @throws \ReflectionException
     */
    public function handle()
    {
        $this->info('*** Import Categories Dispatched ***');
        /*
         * Dispatches job to sync all categories
        */
        ImportCategories::dispatch();
        
    }
}
