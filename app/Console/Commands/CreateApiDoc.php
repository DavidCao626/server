<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Utils\CreateApiDoc as ApiDoc;
class CreateApiDoc extends Command
{
    /**
     * 控制台命令名称.
     *
     * @var string
     */
    protected $signature = 'eoffice:document {version}';

    /**
     * 控制台命令描述.
     *
     * @var string
     */
    protected $description = 'Create e-office 10.0 api document';
    /**
     * 执行控制台命令.
     *
     * @return mixed
     */
    public function handle()
    {
        $apiDoc = new ApiDoc();
        
        $apiDoc->run($this->argument('version'));
    }
}
