<?php

namespace App\Jobs;

use Eoffice;
use App\EofficeApp\Customer\Services\CustomerService;

class customerBirthdayRemindJob extends Job
{
    /**
     * 客户生日队列任务
     *
     * @return void
     */
    public function __construct(CustomerService $customerService)
    {
        $this->customerService = $customerService;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if ($messages = $this->customerService->customerBirthdayRemind()) {
            foreach ($messages as $message) {
                Eoffice::sendMessage($message);
            }
        }
    }
}
