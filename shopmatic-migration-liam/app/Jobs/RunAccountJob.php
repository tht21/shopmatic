<?php

namespace App\Jobs;

use App\Models\Account;
use App\Constants\AccountStatus;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RunAccountJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 900;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 1;

    protected $account;

    protected $method;

    /**
     * Create a new job instance.
     *
     * @param Account $account
     * @param $method
     */
    public function __construct(Account $account,  $method)
    {
        $this->account = $account;
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
        $account = $this->account->fresh();

        // This is if the account got deleted
        if (empty($account)) {
            return;
        }

        // Probably means it got disabled or authorization token expired prior to the job being run
        if ($account->status !== AccountStatus::ACTIVE()->getValue()) {
            return;
        }

        $account->getClient()->{$this->method}();

    }

    /**
     * The job failed to process.
     *
     * @param  \Exception  $exception
     * @return void
     */
    public function failed(\Exception $exception)
    {
        set_log_extra('account', $this->account);
        set_log_extra('method', $this->method);
        Log::error($exception);
    }

}
