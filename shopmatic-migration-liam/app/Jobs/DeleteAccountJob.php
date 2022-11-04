<?php

namespace App\Jobs;

use App\Constants\JobStatus;
use App\Models\Account;
use App\Models\DeleteAccountTask;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DeleteAccountJob implements ShouldQueue
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

    protected $task;

    /**
     * Create a new job instance.
     *
     * @param \App\Models\DeleteAccountTask $task
     */
    public function __construct(DeleteAccountTask $task)
    {
        $this->task = $task;
    }

    /**
     * Execute the job.
     *
     * @return bool
     * @throws \Exception
     */
    public function handle()
    {
        $this->task->status = JobStatus::PROCESSING()->getValue();
        $this->task->save();

        return $this->delete();
    }

    /**
     * Delete account
     *
     * @return bool
     * @throws \Exception
     */
    public function delete()
    {
        /** @var Account $account */
        $account = Account::find($this->task->account_id);

        if (empty($account)) {
            throw new \Exception('Account not found or valid.');
        }

        $productListings = $account->listings()->whereNull('product_variant_id')->get();

        foreach ($productListings as $productListing) {
            $options = $this->task->settings['options'] ?? [];

            // Actual processing
            $adapter = $account->getProductAdapter();
            $adapter->deleteProductListing($productListing, true, $options);
        }

        // Delete account
        $account->delete();

        $this->task->status = JobStatus::FINISHED();
        $this->task->save();

        return true;
    }

    /**
     * The job failed to process.
     *
     * @param  \Exception  $exception
     * @return void
     */
    public function failed(\Exception $exception)
    {
        set_log_extra('task', $this->task->toArray());
        $this->task->messages = [$exception->getMessage()];
        $this->task->status = JobStatus::FAILED();
        $this->task->save();
    }
}
