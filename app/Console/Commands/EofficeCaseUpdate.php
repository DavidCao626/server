<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Update\EofficeCaseUpdate as Update;
class EofficeCaseUpdate extends Command
{
       /**
     * 命令行的名称及用法。
     *
     * @var string
     */
    protected $signature = 'eofficeCase:update {version?} {case_id?}';

    /**
     * 命令行的概述。
     *
     * @var string
     */
    protected $description = 'Used run e-office 10.0 case-update-script';

    /**
     * 滴灌电子邮件服务。
     *
     * @var DripEmailer
     */
    protected $update;

    /**
     * 创建新的命令实例。
     *
     * @param  DripEmailer  $drip
     * @return void
     */
    public function __construct(Update $update)
    {
        parent::__construct();

        $this->update = $update;
    }

    /**
     * 运行命令。
     *
     * @return mixed
     */
    public function handle()
    {
        $this->update->run($this->argument('version'), $this->argument('case_id'));
    }
}
