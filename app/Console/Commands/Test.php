<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Tests\Tester;
class Test extends Command
{
       /**
     * 命令行的名称及用法。
     *
     * @var string
     */
    protected $signature = 'eoffice:test {commands}';

    /**
     * 命令行的概述。
     *
     * @var string
     */
    protected $description = 'Used run e-office 10.0 test-script';

    /**
     * 测试服务。
     *
     * @var DripEmailer
     */
    protected $tester;

    /**
     * 创建新的命令实例。
     *
     * @return void
     */
    public function __construct(Tester $tester)
    {
        parent::__construct();

        $this->tester = $tester;
    }

    /**
     * 运行命令。
     *
     * @return mixed
     */
    public function handle()
    {
        $commands = $this->argument('commands');
        list($module, $tester) = explode(':', $commands);
        $this->tester->run($module, $tester);
    }
}
