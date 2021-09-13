<?php
namespace App\Utils\AppPush\Base;

class BasePush
{
    protected $loggerPath;
    
    public function __construct() 
    {
        $this->loggerPath = storage_path('logs/app-push/');
        if (!is_dir($this->loggerPath)) {
            mkdir($this->loggerPath, 0777);
        }
    }
}
