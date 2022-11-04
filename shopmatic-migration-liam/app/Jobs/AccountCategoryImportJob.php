<?php

namespace App\Jobs;

use App\Constants\IntegrationSyncData;
use App\Constants\JobStatus;
use App\Models\Account;
use App\Models\AccountCategoryImportTask;
use App\Models\ProductImportTask;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class AccountCategoryImportJob implements ShouldQueue
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
     * @param AccountCategoryImportTask $task
     */
    public function __construct(AccountCategoryImportTask $task)
    {
        $this->task = $task;
    }

    /**
     * Execute the job.
     *
     * @return void
     * @throws Exception
     */
    public function handle()
    {
        $this->task->status = JobStatus::PROCESSING()->getValue();
        $this->task->save();

        switch ($this->task->source_type) {
            case Account::class:
                return $this->handleAccount();
            default:
                throw new \Exception('Unhandled source for import account category task - ' . $this->task->source_type);
        }
    }

    /**
     * Fetches the account categories from the account
     *
     * @throws \Exception
     */
    private function handleAccount()
    {
        /** @var Account $account */
        $account = Account::find($this->task->source);

        // Validation prior to actual processing

        if (empty($account)) {
            throw new \Exception('Account not found or valid.');
        }
        if (!$account->hasFeature(['products', 'import_account_categories'])) {
            throw new \Exception('Integration does not support importing of account categories.');
        }

        // Actual processing
        $adapter = $account->getProductAdapter();

        $adapter->importCategories($this->task);

        // Updating the timestamp for the last import time
        $account->setSyncData(IntegrationSyncData::IMPORT_ACCOUNT_CATEGORIES(), now());
        $account->save();

        $this->task->status = JobStatus::FINISHED();
        $this->task->save();
    }


    /**
     * The job failed to process.
     *
     * @param  Exception  $exception
     * @return void
     */
    public function failed(Exception $exception)
    {
        set_log_extra('task', $this->task->toArray());
        Log::error($exception);
        $this->task->messages = [$exception->getMessage()];
        $this->task->status = JobStatus::FAILED();
        $this->task->save();

        # Check if have link to product import task
        if (isset($this->task->settings['product_import_job']) && !empty($this->task->settings['product_import_job'])) {
            # Set to failed status as well
            $productImportJobTask = ProductImportTask::find($this->task->settings['product_import_job']);
            $productImportJobTask->status = JobStatus::FAILED();
            $productImportJobTask->messages = [$exception->getMessage()];
            $productImportJobTask->save();
        }
    }
}
