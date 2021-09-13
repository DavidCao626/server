<?php

namespace App\EofficeApp\Elastic\Controllers;

use App\EofficeApp\Base\Controller;
use App\EofficeApp\Elastic\Configurations\Constant;
use App\EofficeApp\Elastic\Foundation\SearchParams;
use App\EofficeApp\Elastic\Requests\ElasticRequest;
use App\EofficeApp\Elastic\Services\Config\SearchConfigManager;
use App\EofficeApp\Elastic\Services\Dictionary\ExtensionDictionaryService;
use App\EofficeApp\Elastic\Services\Dictionary\SynonymDictionaryService;
use App\EofficeApp\Elastic\Services\Document\DocumentManager;
use App\EofficeApp\Elastic\Services\Log\LogService;
use App\EofficeApp\Elastic\Services\Options\RebuildService;
use App\EofficeApp\Elastic\Services\Options\ESServiceOperationsService;
use App\EofficeApp\Elastic\Services\Search\SearchParamsService;
use App\EofficeApp\Elastic\Services\Search\SearchService;
use App\EofficeApp\Elastic\Services\ServiceManagementPlatform\ManagementPlatformService;
use App\EofficeApp\Elastic\Services\ServiceManagementPlatform\PlatformAuthService;
use App\EofficeApp\Empower\Services\EmpowerService;
use App\Utils\Register;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * es搜索控制器
 */
class ElasticController extends Controller
{
    /**
     * @param Request $request
     */
    private $request;

    public function __construct(Request $request, ElasticRequest $elasticRequest)
    {
        parent::__construct();

        $this->request = $request;

        // POST和PUT表单验证
        $this->formFilter($request, $elasticRequest);
    }

    /**
     * 全站搜索功能
     */
    public function searchAction()
    {
        /** @var SearchParamsService $paramsService */
        $paramsService = app('App\EofficeApp\Elastic\Services\Search\SearchParamsService');

        /** @var SearchParams $param */
        $param = $paramsService->build($this->request, $this->own);
        $index = $this->request->query->get('index', '');
        // 若查询无权限模块, 返回空
        if (!$param->getFilters()) {
            return $this->returnResult(['list' => [], 'total' => 0, 'index' => $index]);
        }

        /** @var SearchService  $searchService */
        $searchService = app('App\EofficeApp\Elastic\Services\Search\SearchService');

        try {
            // TODO 1.前端提示未运行 2.索引不存在不应该报错 3. 每次检查是否过慢 周期性从缓存中获取?或者直接请求 异常捕捉中再进行运行判断
            // 处理搜索响应
            $result = $searchService->search($param, $this->own);
        } catch (\Exception $exception) {
            $message = $exception->getMessage();
            Log::error($message);
            Log::error($exception->getTraceAsString());
            // Elasticsearch 未运行
            if ($message === 'No alive nodes found in your cluster') {
                return $this->returnResult(['code' => ['0x055005', 'elastic']]);
            }

            return $this->returnResult(['list' => [], 'total' => 0, 'index' => $index]);
        }

        $result = array_merge($result, ['index' => $index]);

        return $this->returnResult($result);
    }

    /**
     * 获取全局配置接口
     *  1. 基本配置信息
     *  2. 运行配置
     */
    public function getConfigDetailAction()
    {
        /** @var SearchConfigManager $configService */
        $configService = app('App\EofficeApp\Elastic\Services\Config\SearchConfigManager');

        $data = $configService->getConfigsByType($this->request);;

        return $this->returnResult($data);
    }

    /**
     * 更新ES运行配置
     *  1. elasticsearch
     *  2. jvm
     *  3. log4j2
     */
    public function updateRunConfigAction()
    {
        /** @var SearchConfigManager $configService */
        $configService = app('App\EofficeApp\Elastic\Services\Config\SearchConfigManager');

        // 更新运行配置
        $configService->updateRunConfig($this->request);

        return $this->returnResult([]);
    }

    /**
     * 获取全站搜索配置
     *  1. 全站搜索配置
     *  2. 全站搜索schedule配置
     *  3. 全站搜索queue配置
     */
    public function getGlobalSearchConfigAction()
    {
        /** @var SearchConfigManager $configService */
        $configService = app('App\EofficeApp\Elastic\Services\Config\SearchConfigManager');

        $data = $configService->getGlobalSearchConfigInfo($this->request);;

        return $this->returnResult($data);
    }

    /**
     * 更新全站搜索配置
     *  1. 全站搜索配置
     *  2. 全站搜索schedule配置
     *  3. 全站搜索queue配置
     */
    public function updateGlobalSearchConfigAction()
    {
        /** @var SearchConfigManager $configService */
        $configService = app('App\EofficeApp\Elastic\Services\Config\SearchConfigManager');

        $res = $configService->setGlobalSearchConfig($this->request, $this->own);

        return $this->returnResult($res);
    }

    /**
     * 搜索索引版本切换
     */
    public function switchSearchIndicesVersion()
    {
        /** @var SearchConfigManager $configService */
        $configService = app('App\EofficeApp\Elastic\Services\Config\SearchConfigManager');

        $response = $configService->switchIndicesVersion($this->request);

        return $this->returnResult($response);
    }

    /**
     * 获取别名对应索引
     */
    public function getSearchIndicesByCategory()
    {
        /** @var SearchConfigManager $configService */
        $configService = app('App\EofficeApp\Elastic\Services\Config\SearchConfigManager');

        $response = $configService->getSearchIndicesByCategory($this->request);

        return $this->returnResult($response);
    }

    /**
     * 根据分析器获取分词, 用于分词测试
     */
    public function getTokensByAnalyzer()
    {
        /** @var SearchConfigManager $configService */
        $configService = app('App\EofficeApp\Elastic\Services\Config\SearchConfigManager');

        $data = $configService->getTokensByAnalyzer($this->request);

        return $this->returnResult($data);
    }

    /**
     * 获取ES服务运行状态
     */
    public function getESServiceStatus()
    {
        /** @var SearchService  $searchService */
        $searchService = app('App\EofficeApp\Elastic\Services\Search\SearchService');

        $result = $searchService->isElasticSearchRun();

        return $this->returnResult(['isRunning' => $result]);
    }

    /**
     * 安装/卸载/启动/停止 服务
     */
    public function operateESService()
    {
        /** @var ESServiceOperationsService $rebuildService */
        $rebuildService = app('App\EofficeApp\Elastic\Services\Options\ESServiceOperationsService');

        $rebuildService->operateService($this->request);

        return $this->returnResult([]);
    }

    /**
     * 更新索引
     */
    public function createGlobalSearchIndex()
    {
        //判断es是否运行
        /** @var SearchService  $searchService */
        $searchService = app('App\EofficeApp\Elastic\Services\Search\SearchService');

        if (!$searchService->isElasticSearchRun()){
            return $this->returnResult(['code' => ['0x055005', 'elastic']]);
        }

        /** @var RebuildService $rebuildService */
        $rebuildService = app('App\EofficeApp\Elastic\Services\Options\RebuildService');
        $result = $rebuildService->rebuildGlobalSearch($this->request, $this->own);

        return $this->returnResult($result);
    }

    /**
     * 删除索引
     */
    public function deleteIndices()
    {
        /** @var SearchConfigManager $configService */
        $configService = app('App\EofficeApp\Elastic\Services\Config\SearchConfigManager');

        // 删除指定索引
        $configService->deleteIndex($this->request);

        return $this->returnResult([]);
    }

    /**
     * 初始化配置
     */
    public function initConfigs()
    {
        /** @var SearchConfigManager $configService */
        $configService = app('App\EofficeApp\Elastic\Services\Config\SearchConfigManager');
        // TODO
    }

    /**
     * 获取索引粒度
     */
    public function getWordsSegmentation()
    {
        /** @var SearchConfigManager $configService */
        $configService = app('App\EofficeApp\Elastic\Services\Config\SearchConfigManager');

        $result = $configService->getAnalyzer();

        return $this->returnResult($result);
    }

    /**
     * 索引粒度切换
     */
    public function switchWordsSegmentation()
    {
        /** @var SearchConfigManager $configService */
        $configService = app('App\EofficeApp\Elastic\Services\Config\SearchConfigManager');

        $configService->updateAnalyzer($this->request);

        return $this->returnResult([]);
    }

    /**
     * 获取索引更新进度
     */
    public function getIndexUpdateProcess()
    {
        /** @var SearchConfigManager $configService */
        $configService = app('App\EofficeApp\Elastic\Services\Config\SearchConfigManager');

        $res = $configService->getIndexUpdateProcess($this->request);

        return $this->returnResult($res);
    }

    /**
     * 获取索引上次更新时间
     */
    public function getIndexUpdateRecord()
    {
        /** @var LogService $service */
        $service = app('App\EofficeApp\Elastic\Services\Log\LogService');

        $res = $service->getIndexUpdateTimeByManual();

        return $this->returnResult($res);
    }
    /**
     * 获取同义词典
     */
    public function getSynonymWords()
    {
        /** @var SynonymDictionaryService $service */
        $service = app('App\EofficeApp\Elastic\Services\Dictionary\SynonymDictionaryService');

        $data = $service->getSynonymWords($this->request);;

        return $this->returnResult($data);
    }

    /**
     * 配置同义词典
     */
    public function updateSynonymWords()
    {
        /** @var SynonymDictionaryService $service */
        $service = app('App\EofficeApp\Elastic\Services\Dictionary\SynonymDictionaryService');
        $updateResult = $service->updateSynonymWords($this->request, $this->own);

        return $this->returnResult($updateResult);
    }

    /**
     * 删除同义词
     */
    public function removeSynonymWords()
    {
        /** @var SynonymDictionaryService $service */
        $service = app('App\EofficeApp\Elastic\Services\Dictionary\SynonymDictionaryService');
        $dealResult = $service->removeSynonymWords($this->request, $this->own);

        return $this->returnResult($dealResult);
    }

    /**
     * 还原同义词
     */
    public function restoreSynonymWords()
    {
        /** @var SynonymDictionaryService $service */
        $service = app('App\EofficeApp\Elastic\Services\Dictionary\SynonymDictionaryService');
        $service->restoreSynonymWords($this->request, $this->own);

        return $this->returnResult([]);
    }

    /**
     * 同步同义词典
     */
    public function syncSynonymWords()
    {
        /** @var SynonymDictionaryService $service */
        $service = app('App\EofficeApp\Elastic\Services\Dictionary\SynonymDictionaryService');
        $service->syncSynonymWords();

        return $this->returnResult([]);
    }

    /**
     * 获取扩展词典
     */
    public function getExtensionWords()
    {
        /** @var ExtensionDictionaryService $service */
        $service = app('App\EofficeApp\Elastic\Services\Dictionary\ExtensionDictionaryService');

        $data = $service->getExtensionWords($this->request);;

        return $this->returnResult($data);
    }

    /**
     * 更新扩展词典
     */
    public function updateExtensionWords()
    {
        /** @var ExtensionDictionaryService $service */
        $service = app('App\EofficeApp\Elastic\Services\Dictionary\ExtensionDictionaryService');
        $updateResult = $service->updateExtensionWords($this->request, $this->own);

        return $this->returnResult($updateResult);
    }

    /**
     * 删除扩展词
     */
    public function removeExtensionWords()
    {
        /** @var ExtensionDictionaryService $service */
        $service = app('App\EofficeApp\Elastic\Services\Dictionary\ExtensionDictionaryService');
        $dealResult = $service->removeExtensionWords($this->request, $this->own);

        return $this->returnResult($dealResult);
    }

    /**
     * 还原扩展词
     */
    public function restoreExtensionWords()
    {
        /** @var ExtensionDictionaryService $service */
        $service = app('App\EofficeApp\Elastic\Services\Dictionary\ExtensionDictionaryService');
        $service->restoreExtensionWords($this->request, $this->own);

        return $this->returnResult([]);
    }

    /**
     * 远程扩展词热更新
     */
    public function hotUpdateExtensionDictionary()
    {
        /** @var SearchConfigManager $configService */
        $configService = app('App\EofficeApp\Elastic\Services\Config\SearchConfigManager');
        return $configService->getDictWordsData($this->request, []);
    }

    /**
     * 同步扩展词词典
     */
    public function syncExtensionWords()
    {
        /** @var ExtensionDictionaryService $service */
        $service = app('App\EofficeApp\Elastic\Services\Dictionary\ExtensionDictionaryService');
        $service->syncExtensionWords();

        return $this->returnResult([]);
    }

    /**
     * 获取更新日志列表
     */
    public function getUpdateRecordLogList()
    {
        /** @var LogService $service */
        $service = app('App\EofficeApp\Elastic\Services\Log\LogService');
        $data = $service->getUpdateRecordList($this->request);

        return $this->returnResult($data);
    }

    /**
     * 获取操作日志列表
     */
    public function getOperationRecordLogList()
    {
        /** @var LogService $service */
        $service = app('App\EofficeApp\Elastic\Services\Log\LogService');
        $data = $service->getOperationRecordList($this->request);

        return $this->returnResult($data);
    }

    /**
     * 相关测试
     */
    public function esFunctionTest()
    {
        /** @var DocumentManager $manager */
        $manager = app('App\EofficeApp\Elastic\Services\Document\DocumentManager');
        $manager->readContent(['type' => 'png', 'path'=> 'F:\e-office10\attachment\test.png']);
    }

    /**
     * 注册全文索引菜单
     *  服务管理平台使用
     */
    public function registerElasticMenu()
    {
        $response = [
            'status' => 'success',
            'message' => 'ok',
        ];

        try {
            // 1. 验证是否来自服务管理平台
            /** @var PlatformAuthService $authService */
            $authService = app('App\EofficeApp\Elastic\Services\ServiceManagementPlatform\PlatformAuthService');
            $authService->validRequest($this->request->headers);

            // 2. 注册es菜单
            /** @var ManagementPlatformService $managerService */
            $managerService = app('App\EofficeApp\Elastic\Services\ServiceManagementPlatform\ManagementPlatformService');
            $managerService->menuRegister();
        } catch (\Exception $exception) {
            Log::error($exception->getTraceAsString());
            $response['status'] = 'error';
            $response['message'] = $exception->getMessage();
        }

        return $this->returnResult($response);
    }

    /**
     * 判断是否需要注册es菜单
     *  服务管理平台使用
     */
    public function getElasticMenuUpdateInfo()
    {
        $response = [
            'status' => 'success',
            'message' => 'ok',
            'isAuthorized' => true,
            'isAcceptableVersion' => true,
            'isMenuRegistered' => false,
        ];

        try {
            // 0. 判断模块是否授权
            // 若为临时授权则无模块限制
            /** @var Register $app */
            $app = app('App\Utils\Register');
            $isPermanentUser = $app->isPermanentUser(); // 0为临时 1为永久
            /** @var EmpowerService $empowerService */
            $empowerService = app('App\EofficeApp\Empower\Services\EmpowerService');
            // 若为永久授权则需模块授权
            $isAuthorized = $empowerService->checkModuleWhetherExpired(Constant::ELASTIC_MENU_Id);
            if($isPermanentUser && !$isAuthorized) {
                $response['isAuthorized'] = false;

                return $this->returnResult($response);
            }
            // 1. 验证是否来自服务管理平台
            /** @var PlatformAuthService $authService */
            $authService = app('App\EofficeApp\Elastic\Services\ServiceManagementPlatform\PlatformAuthService');
            $authService->validRequest($this->request->headers);
            // 2. 判断系统版本是否已升级
            /** @var ManagementPlatformService $managerService */
            $managerService = app('App\EofficeApp\Elastic\Services\ServiceManagementPlatform\ManagementPlatformService');
            if(!$managerService->isAcceptableVersion()) {
                $response['isAcceptableVersion'] = false;

                return $this->returnResult($response);
            }
            // 3. 判断是否已注册es模块
            if ($managerService->hasRegistered()) {
                $response['isMenuRegistered'] = true;

                return $this->returnResult($response);
            }
        } catch (\Exception $exception) {
            Log::error($exception->getTraceAsString());
            $response['status'] = 'error';
            $response['message'] = $exception->getMessage();
        }

        return $this->returnResult($response);
    }

    /**
     * 移除已注册es菜单
     *  服务管理平台使用
     */
    public function removeElasticMenu()
    {
        $response = [
            'status' => 'success',
            'message' => 'ok',
        ];

        try {
            // 1. 验证是否来自服务管理平台
            /** @var PlatformAuthService $authService */
            $authService = app('App\EofficeApp\Elastic\Services\ServiceManagementPlatform\PlatformAuthService');
            $authService->validRequest($this->request->headers);
            // 2. 移除相关 菜单
            /** @var ManagementPlatformService $managerService */
            $managerService = app('App\EofficeApp\Elastic\Services\ServiceManagementPlatform\ManagementPlatformService');
            $managerService->removeMenu();
            $managerService->setPlugInStatus();
        } catch (\Exception $exception) {
            Log::error($exception->getTraceAsString());
            $response['status'] = 'error';
            $response['message'] = $exception->getMessage();
        }

        return $this->returnResult($response);
    }

    /**
     * 获取es是否运行
     *  服务管理平台使用
     */
    public function isElasticRunning()
    {
        $response = [
            'status' => 'success',
            'message' => 'ok',
            'isRunning' => false,
        ];
        try {
            /** @var SearchService  $searchService */
            $searchService = app('App\EofficeApp\Elastic\Services\Search\SearchService');
            $response['isRunning'] = $searchService->isElasticSearchRun();
        } catch (\Exception $exception) {
            Log::error($exception->getTraceAsString());
            $response['status'] = 'error';
            $response['message'] = $exception->getMessage();
        }

        return $this->returnResult($response);
    }

    /**
     * 迁移es数据
     *  服务管理平台使用
     */
    public function migrationData()
    {
        $response = [
            'status' => 'success',
            'message' => 'ok',
        ];

        try {
            // 1. 验证是否来自服务管理平台
            /** @var PlatformAuthService $authService */
            $authService = app('App\EofficeApp\Elastic\Services\ServiceManagementPlatform\PlatformAuthService');
            $authService->validRequest($this->request->headers);
            // 2. 迁移数据
            /** @var ManagementPlatformService $managerService */
            $managerService = app('App\EofficeApp\Elastic\Services\ServiceManagementPlatform\ManagementPlatformService');
            $managerService->migrationData();
        } catch (\Exception $exception) {
            Log::error($exception->getTraceAsString());
            $response['status'] = 'error';
            $response['message'] = $exception->getMessage();
        }

        return $this->returnResult($response);
    }

    /**
     * 获取数据迁移状态
     *  服务管理平台使用
     */
    public function getMigrationDetail()
    {
        $response = [
            'status' => 'success',
            'message' => 'ok',
            'isRunning' => true,
            'runningCategory' => '',
            'finishedCategory' => [],
            'preparedCategory' => [],
            'currentCategoryUpdateProcess' => 0,
            'allCategoriesUpdateProcess' => '0/14',
            'allCategoriesPercentageProcess' => 100,
        ];

        try {
            // 1. 验证是否来自服务管理平台
            /** @var PlatformAuthService $authService */
            $authService = app('App\EofficeApp\Elastic\Services\ServiceManagementPlatform\PlatformAuthService');
            $authService->validRequest($this->request->headers);
            // 2. 获取迁移数据
            /** @var ManagementPlatformService $managerService */
            $managerService = app('App\EofficeApp\Elastic\Services\ServiceManagementPlatform\ManagementPlatformService');
            $info = $managerService->getMigrationData();
            $response = array_merge($response, $info);
        } catch (\Exception $exception) {
            Log::error($exception->getTraceAsString());
            $response['status'] = 'error';
            $response['message'] = $exception->getMessage();
        }

        return $this->returnResult($response);
    }

    /**
     * 处理测试数据接口
     *  服务管理平台使用
     */
    public function dealTestData()
    {
        $response = [
            'status' => 'success',
            'message' => 'ok',
            'redisData' => [],
        ];

        try {
            // 1. 验证是否来自服务管理平台
            /** @var PlatformAuthService $authService */
            $authService = app('App\EofficeApp\Elastic\Services\ServiceManagementPlatform\PlatformAuthService');
            $authService->validRequest($this->request->headers);
            // 2. 移除相关 菜单
            /** @var ManagementPlatformService $managerService */
            $managerService = app('App\EofficeApp\Elastic\Services\ServiceManagementPlatform\ManagementPlatformService');
            $empty = $this->request->get('emptyRedisData', false);
            $data = $managerService->dealTestDataInRedis($empty);
            $response['redisData'] = $data;
        } catch (\Exception $exception) {
            Log::error($exception->getTraceAsString());
            $response['status'] = 'error';
            $response['message'] = $exception->getMessage();
        }

        return $this->returnResult($response);
    }

    /**
     * 开始手动更新时判断队列是否开启
     */
    public function getQueueStatusBeforeUpdateByManual()
    {
        /** @var ManagementPlatformService $service */
        $service = app('App\EofficeApp\Elastic\Services\ServiceManagementPlatform\ManagementPlatformService');
        $status = $service->getQueueStatusBeforeUpdateByManual();

        return $this->returnResult($status);
    }

    /**
     * 判断正在进行手动更新的队列是否因意外停止
     */
    public function getQueueStatusInUpdateByManual()
    {
        /** @var ManagementPlatformService $service */
        $service = app('App\EofficeApp\Elastic\Services\ServiceManagementPlatform\ManagementPlatformService');
        $status = $service->getQueueStatusInUpdateByManual();

        return $this->returnResult($status);
    }

    /**
     * 清空redis中es的更新记录
     */
    public function clearUpdateProcess()
    {
        /** @var ManagementPlatformService $service */
        $service = app('App\EofficeApp\Elastic\Services\ServiceManagementPlatform\ManagementPlatformService');
        $service->clearUpdateProcess();

        return $this->returnResult([]);
    }
}