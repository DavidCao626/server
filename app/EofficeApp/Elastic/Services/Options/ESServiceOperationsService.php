<?php


namespace App\EofficeApp\Elastic\Services\Options;


use App\EofficeApp\Elastic\Repositories\ElasticSearchConfigRepository;
use App\EofficeApp\Elastic\Services\BaseService;
use App\EofficeApp\Elastic\Services\Config\SearchConfigManager;
use Illuminate\Http\Request;

class ESServiceOperationsService extends BaseService
{
    /**
     * @var SearchConfigManager $managerService
     */
    private $managerService;

    /**
     * @var ElasticSearchConfigRepository $repository
     */
    private $repository;

    public function __construct()
    {
        parent::__construct();
        $this->managerService = app('App\EofficeApp\Elastic\Services\Config\SearchConfigManager');
        $this->repository = app('App\EofficeApp\Elastic\Repositories\ElasticSearchConfigRepository');
    }

    /**
     * ES服务相关操作
     */
    public function operateService(Request $request)
    {
        $option = $request->request->get('option');
        $value = $request->request->get('value', '');

        if (is_callable([self::class, $option])) {
            call_user_func([self::class, $option], $value);
        }
    }

    /**
     * 重新安装ES服务
     */
    protected function reinstall()
    {
        $path = $this->managerService->getEsBasePathInfo();
        $esPathInfo = $path['esPath'];

        // TODO 后续将逻辑移至此处
        return $this->managerService->reinstallService($esPathInfo);
    }

    /**
     * 重启ES服务
     */
    protected function restart()
    {
        $path = $this->managerService->getEsBasePathInfo();
        $esPathInfo = $path['esPath'];

        // TODO 后续将逻辑移至此处
        return $this->managerService->restartService($esPathInfo, false);
    }
}