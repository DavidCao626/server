<?php


namespace App\EofficeApp\System\Params\Services;


use App\EofficeApp\Base\BaseService;
use App\EofficeApp\Document\Services\WPS\WPSAuthService;
use App\EofficeApp\System\Params\Repositories\SystemParamsRepository;
use Illuminate\Support\Facades\Redis;

class SystemParamsService extends BaseService
{
    /**
     * @var string $systemParamRepository
     */
    private $systemParamRepository = 'App\EofficeApp\System\Params\Repositories\SystemParamsRepository';

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 获取文档插件相关配置
     *
     * @return array
     */
    public function getOnlineReadOption()
    {
        /** @var SystemParamsRepository $repository */
        $repository = app($this->systemParamRepository);

        // TODO validate

        return $repository->getOnlineReadOption();
    }

    /**
     * 批量更新文档插件相关配置
     *
     * @return bool
     */
    public function saveOnlineReadOption($data)
    {
        /** @var SystemParamsRepository $repository */
        $repository = app($this->systemParamRepository);


        // TODO validate 验证各参数的有效性 长度验证 格式验证
        array_walk($data, function(&$value, $key) {
            $value = ['param_key' => trim($key), 'param_value' => trim($value)];
        });

        $rows = $repository->updateBatch($data);

        Redis::del(WPSAuthService::WPS_APP_ID,WPSAuthService::WPS_APP_KEY);

        return (bool) $rows;
    }
}