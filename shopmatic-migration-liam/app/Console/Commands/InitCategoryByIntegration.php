<?php

namespace App\Console\Commands;

use App\Jobs\ImportCategories;
use App\Models\Integration;
use Illuminate\Console\Command;

class InitCategoryByIntegration extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'init:category {integrationName}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'init integration_categories, integration_category_attributes';

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
        $this->info('*** start run init category ***');
        $integrationName = $this->argument('integrationName');
        $integration = Integration::where('name', 'like', '%'.$integrationName.'%')->first();
        if (!$integration) {
            $this->info('*** Integration name not found  ***');
            return;
        }
        $this->info('*** Import Categories Dispatched For Integration '.$integrationName.'***');
        /*
         * Dispatches job to sync all categories MP specific
        */
        ImportCategories::dispatch($integration);
    }
}
