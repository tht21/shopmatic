<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

class ClearCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clear:cache {key?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear cache.';

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
     */
    public function handle()
    {
        $key = $this->argument('key');
        if ($key && !preg_match("/\[.+\]|\*/", $key)) {
            Cache::forget($key);
            $this->info("$key cache has been cleared.");
        } elseif ($key) {
            Redis::del(Redis::keys($key));
            $this->info("All cache with pattern $key has been cleared.");
        } else {
            Artisan::call('cache:clear');
            $this->info('Application cache cleared.');
        }
    }
}
