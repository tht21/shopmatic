<?php

namespace App\Jobs;

use App\Constants\AccountStatus;
use App\Models\Integration;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RunIntegrationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 7200;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 1;

    protected $integration;

    protected $region;

    protected $method;

    /**
     * Create a new job instance.
     *
     * @param Integration $integration
     * @param $region
     * @param $method
     */
    public function __construct(Integration $integration, $region, $method)
    {
        $this->integration = $integration;
        $this->region = $region;
        $this->method = $method;
    }

    /**
     * Execute the job.
     *
     * @return void
     * @throws \Exception
     */
    public function handle()
    {
        $accounts = $this->integration->accounts()
            ->where('status',  AccountStatus::ACTIVE()->getValue())
            ->where('region_id', $this->region)->get();
        foreach ($accounts as $account) {
            RunAccountJob::dispatch($account, $this->method);
        }
    }

    /**
     * The job failed to process.
     *
     * @param  \Exception  $exception
     * @return void
     */
    public function failed(\Exception $exception)
    {
        set_log_extra('integration', $this->integration);
        set_log_extra('region', $this->region);
        set_log_extra('method', $this->method);
        Log::error($exception);
    }

}
