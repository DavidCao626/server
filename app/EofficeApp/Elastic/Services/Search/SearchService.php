<?php


namespace App\EofficeApp\Elastic\Services\Search;

use App\EofficeApp\Address\Services\AddressService;
use App\EofficeApp\Customer\Repositories\CustomerRepository;
use App\EofficeApp\Document\Services\DocumentService;
use App\EofficeApp\Elastic\Configurations\Constant;
use App\EofficeApp\Elastic\Configurations\RedisKey;
use App\EofficeApp\Elastic\Foundation\Params;
use App\EofficeApp\Elastic\Foundation\Query;
use App\EofficeApp\Elastic\Foundation\SearchParams;
use App\EofficeApp\Elastic\Services\BaseService;
use App\EofficeApp\Email\Services\EmailService;
use App\EofficeApp\Flow\Entities\FlowRunEntity;
use App\EofficeApp\Notify\Repositories\NotifyRepository;
use App\EofficeApp\Notify\Services\NotifyService;
use App\EofficeApp\PersonnelFiles\Services\PersonnelFilesPermission;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class SearchService extends BaseService
{
    /**
     * 搜索参数
     *
     * @var SearchParams $params
     */
    private $params;

    /**
     * 用户信息
     *
     * @var array $own
     */
    private  $own;

    /**
     * 搜索响应处理服务
     *
     * @var SearchResultSetService $resultSet
     */
    private $resultSet;

    public function __construct()
    {
        parent::__construct();
        // 响应处理
        $this->resultSet = 'App\EofficeApp\Elastic\Services\Search\SearchResultSetService';
    }

    /**
     * 搜索资源预处理
     *      1. 获取用户配置
     *
     * @param bool  $refresh    是否更新redis中配置
     *
     * @return array
     */
    private function resourcePreprocess($refresh = false)
    {
        $redisKey = RedisKey::REDIS_CONFIG_TERMS_USER_PREFIX.$this->own['user_id'];

        if ($refresh || !($result = Redis::get($redisKey))) {

            // 获取用户对应的所有索引的过滤信息
            $terms = [];
            $cycle = RedisKey::REDIS_CONFIG_USER_CYCLE;

            // 获取用户可以访问的所有索引分类
            $targetCategories = $this->params->getFilters();
            foreach ($targetCategories as $category) {
                $terms[$category] =  $this->getCategoryFilterByTerms($category);
            }

            $value = json_encode($terms);
            if ($refresh) {
                Redis::setex($redisKey, $cycle, $value);
            } else {
                Redis::set($redisKey, $value, 'ex', $cycle, 'nx'); // 周期内存在则不更新
            }

            return $terms;
        }

        return [];
    }

    /**
     * 实现搜索
     *
     * @param SearchParams $params
     * @param array $own
     *
     * @return array
     */
    public function search(Params $params, $own)
    {
        $this->params = $params;
        $this->own = $own;
        // 更新配置
        $this->resourcePreprocess();
        // 同一类型索引同时存在多个, 可根据别名或eoffice前缀搜索
        $alias = $this->getSearchAlias($params->getFilters());
        // 创建searchBody
        $body = $this->getSearchBody();

        $response = $this->client->search([
            'index' => $alias,
            'body' => $body,
        ]);
        /** @var SearchResultSetService $resultSetService */
        $resultSetService = app($this->resultSet);

        // 响应处理
        $resultSet = $resultSetService->buildResult($response);

        return $resultSet;
    }

    /**
     * 构建搜索条件.
     *
     * @return array
     */
    private function getSearchBody()
    {
        $query = new Query();

        // TODO 调试时设置为true
        $query->setExplain($this->params->getExplain());

        $query->setQuery($this->getQuery());

        $query->setFrom($this->params->getFrom());

        $query->setSize($this->params->getSize());

        $query->setSort($this->getSort());

        // TODO 参数调整
        $query->setHighLight();

        return $query->convertArray();
    }

    /**
     * 构建查询体
     *
     * @return array
     *
     *  TODO  后续完善
     *
     * 目前结构体为
     *  query => {
     *      bool => {
     *          should => {
     *              bool => {
     *                  must => {
     *                      multi_match = > { query: ... ,  fields: [...]},
     *                      term => { category: ...},
     *                      terms => {customer_id:[...]}
     *                  }
     *              },
     *              bool => {...},
     *              bool => {...},
     *              ...
     *          }
     *      }
     *  }
     */
    private function getQuery()
    {
        // 查询体暂时只有bool下的should查询
        $query['bool']['should'] = $this->getShouldQuery();
        // 最小匹配参数可以为整数或百分比
        $query['bool']['minimum_should_match'] = $this->getMinShouldMatch();

        return $query;
    }

    /*
     * bool查询, 存在 must 和 must_not子查询
     *
     *  must: 必须满足的查询条件
     *  must_not: 必须过滤掉的查询条件
     *
     * @return array
     */
    private function getBoolQuery($category)
    {
        $boolQuery = [];

        $mustQuery = $this->getMustQuery($category);
        $mustNotQuery = $this->getMustNotQuery($category);

        if ($mustQuery) {
            $boolQuery['must'] = $mustQuery;
        }

       if ($mustNotQuery) {
           $boolQuery['must_not'] = $mustNotQuery;
       }

        return ['bool' => $boolQuery];
    }

    /**
     * must查询, 存在 multi_match  term terms 和 exists类型子查询
     *
     *  multi_match : 多字段查询, 根据分类获取查询字段
     *  term : 按分类过滤
     *  terms : 按照查看请求权限过滤
     *  exists : 字段为非空
     *
     * @param string $category
     *
     * @return array
     */
    private function getMustQuery($category)
    {
        $mustQuery = [
            [
                'multi_match' => [
                    'query' => $this->params->getKeyword(),
                    'fields' =>$this->getFieldsByCategory($category),
                ],
            ],
            [
                'term' => ['category' => $category],
            ],
        ];

        // 若不存在白名单查看控制, 则根据terms进行过滤
        if (!in_array($category, config('elastic.view_white_terms_list'))) {
            // 若 terms 为空, 则不按白名单过滤
            $terms = $this->getCategoryTerms($category);
            if ($terms) {
                $mustQuery[] = ['terms' => $terms];
            }
        }

        // 若存在非空字段过滤, 则根据 exists 进行过滤
        if (array_key_exists($category, config('elastic.view_white_exists_list'))) {

            // 若存在 exist 根据字段进行过滤
            $existsField = config('elastic.view_white_exists_list')[$category];

            $mustQuery[] = ['exists' => ['field' => $existsField]];

        }

        return $mustQuery;
    }

    /**
     * 从redis获取对应的terms信息
     *
     * @return array
     */
    private function getCategoryTerms($category)
    {
        $redisKey = RedisKey::REDIS_CONFIG_TERMS_USER_PREFIX.$this->own['user_id'];

        $userConfig = Redis::get($redisKey);
        $userConfigArr = json_decode($userConfig, true);
        $keys = array_keys($userConfigArr);

        // 若不存在指定分类则更新
        if (!in_array($category, $keys)) {
            $userConfigArr = $this->resourcePreprocess(true);
        }

        if (isset($userConfigArr[$category]) && $userConfigArr[$category]) {
            return $userConfigArr[$category];
        }

        return[];
    }
    /**
     * must_not查询, 目前不存在子查询
     *
     * @param string $category
     *
     * @return array
     */
    private function getMustNotQuery($category)
    {
        $mustNotQuery = [];

        $viewBlackConfig = config('elastic.view_black_list');
        // 若存在黑名单查看控制, 则根据 exists 进行过滤
        if (array_key_exists($category, $viewBlackConfig)) {

            // 获取所有黑名单类型
            $blackList = config('elastic.view_black_list')[$category];
            foreach ($blackList as $type =>  $black) {
                $limit = [];
                switch ($type) {
                    case 'exists':
                        break;
                }
            }

            // 存在限制则添加
            if ($limit) {
                $mustNotQuery[] = $limit;
            }
        }

        return $mustNotQuery;
    }
    /**
     * should查询
     *
     * @return array
     */
    private function getShouldQuery()
    {
        $shouldQueries = array();

        // 获取目标索引, 分别进行布尔查询
        $targetCategories = $this->params->getFilters();

        foreach ($targetCategories as $category) {
            $shouldQueries[] = $this->getBoolQuery($category);
        }

        return $shouldQueries;
    }

    /**
     * 获取最小匹配度
     *
     * @return mixed
     */
    private function getMinShouldMatch()
    {
        return $this->params->getMinShouldMatch() ?? false;
    }

    /**
     * 排序方式
     *  默认为相关度, 若无关键字则按照优先级和创建时间排序
     *
     * @return array
     */
    private function getSort()
    {
        if ($this->params->hasOrders()) {
            return $this->params->getOrders();
        }

        if (!$this->params->hasKeyword()) {
            return [
                'priority' => ['order' => 'desc'],
                'createTime' => ['order' => 'desc'],
            ];
        }

        switch ($this->params->getSort()) {
            case SearchParams::SORT_BY_CREATE_TIME:
                $sort = [
                    'createTime' => ['order' => 'desc'],
                ];
                break;
            case SearchParams::SORT_DEFAULT:
                $sort = [
                    'priority' => ['order' => 'desc'],
                    '_score' => ['order' => 'desc']
                ];
                break;
            default:
                $sort = ['_score' => ['order' => 'desc']];
        }

        return $sort;
    }

    /**
     * 获取可搜索的别名
     *
     * @return array
     */
    public function getSearchAlias($modules)
    {
        // 全部索引
        $allIndices = Constant::$allIndices;
        // 根据查询模块获取所需索引
        $targetIndices = array_intersect($allIndices, $modules);
        // 获取所需索引的别名
        $alias = array_keys($targetIndices);

        return $alias;
    }

    /**
     * 访问权限控制
     *
     * @return array
     */
    private function getCategoryFilterByTerms($category)
    {
        $terms = [];
        $params = [];
        $flag = true;
        switch ($category) {
            // 客户模块, 根据课件客户id过滤
            case Constant::CUSTOMER_CATEGORY:
                $customerIds = CustomerRepository::getViewIds($this->own,[],$params,$flag);

                if ($customerIds == CustomerRepository::ALL_CUSTOMER) {
                    break;
                }
                $terms = ['customer_id' => $customerIds];
                break;
            // 客户联系人模块, 根据可见客户获取响应联系人id进行过滤
            case Constant::CUSTOMER_LINKMAN_CATEGORY:
                // 获取可见的客户id
                $customerIds = CustomerRepository::getViewIds($this->own,[],$params,$flag);
                if ($customerIds == CustomerRepository::ALL_CUSTOMER) {
                    break;
                }
                $query = DB::table('customer_linkman');

                if(!empty($customerIds) && count($customerIds) > CustomerRepository::MAX_WHERE_IN){
                    self::tempTableJoin($query,$customerIds,'customer_linkman');
                }else (
                    $query->whereIn('customer_id',$customerIds)->pluck('linkman_id')->toArray()
                );
                $linkmanIds = $query->pluck('linkman_id')->toArray();

                $terms = ['linkman_id' => $linkmanIds];
                break;
            // 客户业务机会模块, 可对自己创建或者拥有访问客户模块权限的进行访问
            case Constant::CUSTOMER_BUSINESS_CHANCE_CATEGORY:
                $customerIds = CustomerRepository::getViewIds($this->own,[],$params,$flag);
                if ($customerIds == CustomerRepository::ALL_CUSTOMER) {
                    break;
                }
                $chanceIdsByCreator = DB::table('customer_business_chance')->where('chance_creator', $this->own['user_id'])
                    ->pluck('chance_id')->toArray();
                $chanceIdsByCustomer = DB::table('customer_business_chance')->whereIn('customer_id', $customerIds)
                    ->pluck('chance_id')->toArray();
                $chanceIds = array_merge($chanceIdsByCreator, $chanceIdsByCustomer);
                $terms = ['chance_id' => $chanceIds];

                break;
            // 客户合同模块, 可对自己创建或者拥有访问客户模块权限的进行访问
            case Constant::CUSTOMER_CONTRACT_CATEGORY:

                $customerIds = CustomerRepository::getViewIds($this->own,[],$params,$flag);
                if ($customerIds == CustomerRepository::ALL_CUSTOMER) {
                    break;
                }

                $chanceIdsByCreator = DB::table('customer_contract')->where('contract_creator', $this->own['user_id'])
                    ->pluck('contract_id')->toArray();
                $chanceIdsByCustomer = DB::table('customer_contract')->whereIn('customer_id', $customerIds)
                    ->pluck('contract_id')->toArray();
                $chanceIds = array_merge($chanceIdsByCreator, $chanceIdsByCustomer);
                $terms = ['contract_id' => $chanceIds];
                break;
            // 客户提醒模块, 拥有访问客户模块权限的进行访问
            case Constant::CUSTOMER_WILL_VISIT_CATEGORY:
                $customerIds = CustomerRepository::getViewIds($this->own,[],$params,$flag);
                if ($customerIds == CustomerRepository::ALL_CUSTOMER) {
                    break;
                }
                $terms = ['customer_id' => $customerIds];
                break;
            // 客户联系记录模块, 拥有访问客户模块权限的进行访问
            case Constant::CUSTOMER_CONTACT_RECORD_CATEGORY:
                $customerIds = CustomerRepository::getViewIds($this->own,[],$params,$flag);
                if ($customerIds == CustomerRepository::ALL_CUSTOMER) {
                    break;
                }
                $terms = ['customer_id' => $customerIds];
                break;
            // 新闻模块, 根据自己创建或已发布的id进行过滤
            case Constant::NEWS_CATEGORY:
                $newsIds = DB::table('news')->where('publish', 1)
                                                   ->orWhere('creator', $this->own['user_id'])
                                                   ->pluck('news_id')
                                                   ->toArray();

                $terms = ['news_id' => $newsIds];
                break;
            // 文档模块, 根据用户信息获取可访问的文档ids
            case Constant::DOCUMENT_CATEGORY:
                /** @var DocumentService $documentService */
                $documentService = app('App\EofficeApp\Document\Services\DocumentService');
                $documentIds = $documentService->getViewDocumentId($this->own);
                $terms = ['document_id' => $documentIds];
                break;
            // 邮件模块, 根据用户信息获取可访问的邮件ids过滤
            case Constant::EMAIL_CATEGORY:
                $emailIds = EmailService::getUserEmailIds($this->own['user_id']);
                $terms = ['email_id' => $emailIds];
                break;
            // 流程模块
            case Constant::FLOW_CATEGORY:
                /** @var FlowRunEntity $flowEntity */
                $flowEntity = app('App\EofficeApp\Flow\Entities\FlowRunEntity');
                $query = $flowEntity->newQuery();
                $userId = $this->own['user_id'];
                $query->where('is_effect','1');
                if($userId != "admin") {
                    $query = $query->where(function($query) use($userId){
                        $query->orWhereHas("FlowRunHasOneFlowType",function($query) use($userId){
                            $query->whereHas('flowTypeHasManyManageUser', function ($query) use ($userId) {
                                $query->wheres(['user_id' => [$userId]]);
                            });
                        })
                            ->orWhereHas("flowRunHasManyFlowRunStep",function($query) use($userId){
                                $query->where("user_id",$userId);
                            })
                            ->orWhere("flow_run.creator",$userId);
                    });
                }
                $ids = $query->pluck('run_id')->toArray();
                $terms = ['run_id' => $ids];
                break;
            // 人事档案模块
            case Constant::PERSONNEL_FILES_CATEGORY:
                /** @var PersonnelFilesPermission $permission */
                $permission = app('App\EofficeApp\PersonnelFiles\Services\PersonnelFilesPermission');

                $deptIds = $permission->getQueryPermittedDepts($this->own);
                if ($deptIds == 'all') {
                    $deptIds = DB::table('department')->pluck('dept_id')->toArray();
                }
                $ids = DB::table('personnel_files')->whereIn('dept_id', $deptIds)->pluck('id')->toArray();
                $terms = ['id' => $ids];
                break;
            // 个人通讯录
            case Constant::PRIVATE_ADDRESS_CATEGORY:
                $addressIds = DB::table('address_private')->where(['primary_5'=> $this->own['user_id']])
                    ->pluck('address_id')
                    ->toArray();
                $terms = ['address_id' => $addressIds];
                break;
            // 公共通讯录
            case Constant::PUBLIC_ADDRESS_CATEGORY:
                /** @var AddressService $addressService */
                $addressService = app('App\EofficeApp\Address\Services\AddressService');
                $viewAddressIds = $addressService->getFamilyGroupIds(1,0, $this->own);
                $addressIds = DB::table('address_public')->whereIn('primary_4', $viewAddressIds)
                    ->pluck('address_id')
                    ->toArray();

                $terms = ['address_id' => $addressIds];
                break;
            // 公告模块
            case Constant::NOTIFY_CATEGORY:
                /** @var NotifyService $notifyService */
                $notifyService = app('App\EofficeApp\Notify\Services\NotifyService');
                $notifyIds = $notifyService->getAllAccessibleNotifyIdsToPerson($this->own);

                $terms = ['notify_id' => $notifyIds];
                break;
            // 用户模块
            case Constant::USER_CATEGORY:
                // 查询非注销用户, 数量过多 在 exists 中添加, 查询user_accounts非空的用户
                break;
            case Constant::SYSTEM_LOG_CATEGORY:
                // 系统日志模块用户模块权限即可访问
                break;
            default:
                $terms = [];
        }

        return $terms;
    }


    /**
     * 公共处理wherein数据过长导致sql无法查询
     */
    private function tempTableJoin($query,$customerIds,$tableName){
        if (!empty($customerIds) && count($customerIds) > CustomerRepository::MAX_WHERE_IN) {
            $tableName = $tableName.rand() . uniqid();
            DB::statement("CREATE TEMPORARY TABLE if not exists {$tableName} (`data_id` int(6) NOT NULL,PRIMARY KEY (`data_id`))");
            $tempIds = array_chunk($customerIds, CustomerRepository::MAX_WHERE_IN, true);
            foreach ($tempIds as $key => $item) {
                $ids      = implode("),(", $item);
                $tSql = "insert into {$tableName} (data_id) values ({$ids});";
                DB::insert($tSql);
            }
            $query = $query->join("$tableName", $tableName . ".data_id", '=', 'customer_id');
        }
        return $query;
    }

    /**
     * 根据索引类型获取查询字段
     *
     * @return array
     */
    public function getFieldsByCategory($category)
    {
        $fields = [];
        $multiFieldsConfig = config('elastic.category_multi_fields');

        if (isset($multiFieldsConfig[$category])) {
            $fields = $multiFieldsConfig[$category];
        }

        return $fields;
    }
}
