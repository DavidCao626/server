<?php

namespace App\EofficeApp\Project\NewServices\Managers\RolePermission\Bins\ApiBins;

use App\EofficeApp\Project\Entities\ProjectBaseEntity;
use App\EofficeApp\Project\NewRepositories\ProjectManagerRepository;
use App\EofficeApp\Project\NewServices\Managers\DataManager;
use App\EofficeApp\Project\NewServices\Managers\HelpersManager;
use App\EofficeApp\Project\NewServices\Managers\RolePermission\PermissionManager;
use App\Utils\ResponseService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
abstract class BaseApiBin
{
    // 获取
    protected $action = 'action';
    protected $type = 0;
    protected $module = 1;
//    protected $method = 2;
//    protected $path = 3;
//    protected $menu = 4;
    protected $mustParamKeys = 5;
    protected $defaultOrder = 6;
    protected $defaultFpi = 7;
    private $mustParams;

    public function __construct($apiConfig)
    {
        $this->action = $apiConfig[$this->action];
        $this->type = $apiConfig[$this->type];
        $this->module = $apiConfig[$this->module];
        $this->mustParamKeys = $apiConfig[$this->mustParamKeys];
        $this->defaultOrder = $apiConfig[$this->defaultOrder];
        $this->defaultFpi = Arr::get($apiConfig, $this->defaultFpi);
        $this->initMustParams();
    }

    // 验证权限
    public function testPermission()
    {
        $dataManager = DataManager::getIns();
//        $roleFunctionPageModels = DataManager::getIns()->getRoleFunctionPageModels();
//        if (!$roleFunctionPageModels || $roleFunctionPageModels->isEmpty()) {
//            ResponseService::throwException('0x036018', 'project');
//        }
//        if ($testRolePermission) {
//            $projectRoleUserModels = $dataManager->getProjectRoleUserModels();
//            $functionPageRoles = $roleFunctionPageModels->pluck('role_id');
//            $curUserProjectRoles = $projectRoleUserModels->pluck('role_id');
//            $hasPermission = $functionPageRoles->intersect($curUserProjectRoles->toArray())->isNotEmpty(); // 角色是否存在交集
//            if (!$hasPermission) {
//                ResponseService::throwException(['0x036018', 'self'], 'project', ['', 'flag:testPermission2']);
//            }
//        }
        // 开发对list的验证
        // 只有项目列表可跳过权限检测
        if (!($this->type == 'list' && $this->getDefaultFpi() == 'project_list')) {
            $hasPermission = PermissionManager::validPermission($dataManager);
            if (!$hasPermission) {
                ResponseService::throwException('0x036018', 'project');
            }
        }
    }

    // 格式化结果
    public function formatResult(&$result) {
        $dataManager = DataManager::getIns();
        $withProject = $dataManager->getApiParams('@with_project');
        if ($withProject) {
            $newProject = ProjectManagerRepository::buildQuery()->find($dataManager->getManagerId());
            $result['project'] = $newProject;
        }
    }

    // 初始化api的其他数据
    public function initApiData() {}


    private function initMustParams()
    {
        $dataManager = DataManager::getIns();
        $this->mustParams = $dataManager->getApiParams($this->mustParamKeys, 'not set');
        if (in_array('not set', $this->mustParams)) {
            ResponseService::throwException(['0x036029', 'self'], 'project', ['', 'flag:initMustParams']);
        }
        if ($managerId = Arr::get($this->mustParams, 'manager_id')) {
            $project = ProjectManagerRepository::buildQuery(['manager_id' => $managerId])->first();
            $dataManager->setManagerId($managerId);
            $dataManager->setProject($project);
            if (!$project) {
                ResponseService::throwException('no_data', 'common');
            }
            if ($this->module == 'project') {
                $dataManager->setRelationId([$managerId]);
                $dataManager->setRelations(collect()->push($project));
            }
        }
        if ($relationId = Arr::get($this->mustParams, 'relation_id')) {
            $relationId = explode(',', $relationId);
            $relationId = array_filter(array_unique($relationId));
            $mapping = ProjectBaseEntity::getProjectTableMapping();
            $projectField = ProjectBaseEntity::getRelationProjectField($this->module);
            if ($relationId && isset($mapping[$this->module])) {
                $class = $mapping[$this->module];
                $query = $class::query()->whereKey($relationId);
                $projectField && $query->where($projectField, $managerId);
                $relations =  $query->get();
                // 无数据或者数据不等，抛出错误
                if ($relations->isEmpty() || $relations->count() !== count($relationId)) {
                    ResponseService::throwException('no_data', 'common');
                }
                $dataManager->setRelationId($relationId);
                $dataManager->setRelations($relations);
            } else {
                ResponseService::throwException('0x036001', 'project');
            }
        }
    }

    #########config方面得###############

    protected function fillData(Model &$model, $data, $config)
    {
        // 过滤填充字段
        $allowFields = Arr::get($config, 'allow_fields', []);
        if ($allowFields && is_array($allowFields)) {
            $type = Arr::get($allowFields, 'type');
            $value = Arr::get($allowFields, 'values');
            if (is_array($value)) {
                $value = array_flip($value);
                $type === 'white' && $data = array_intersect_key($data, $value);
                $type === 'black' && $data = array_diff_key($data, $value);
            }
        }

        $model->fill($data);

        // 填充固定数据，可以绕过上面的黑白名单
        $fixedFieldData = Arr::get($config, 'fixed_field_data', []);
        if ($fixedFieldData && is_array($fixedFieldData)) {
            foreach ($fixedFieldData as $fieldName => $value) {
                switch ($value) {
                    case 'currentDatetime':
                        $value = date('Y-m-d H:i:s');
                        break;
                    case 'currentDate':
                        $value = date('Y-m-d');
                        break;
                    case 'currentUser':
                        $value = DataManager::getIns()->getCurUserId();
                        break;
                }
                $model[$fieldName] = $value;
            }
        }

        $filter = Arr::get($config, 'test_fields', []);
        if ($filter && !HelpersManager::testFilter($model, $filter)) {
            ResponseService::throwException('0x036024', 'project'); // 数据不合法，刷新重试
        }
    }



    /**
     * @return string
     */
    public function getAction(): string
    {
        return $this->action;
    }

    /**
     * @param string $action
     */
    public function setAction(string $action): void
    {
        $this->action = $action;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param int $type
     */
    public function setType(int $type): void
    {
        $this->type = $type;
    }

    /**
     * @return string
     */
    public function getModule()
    {
        return $this->module;
    }

    /**
     * @param int $module
     */
    public function setModule(int $module): void
    {
        $this->module = $module;
    }

    /**
     * @return mixed
     */
    public function getMustParamKeys()
    {
        return $this->mustParamKeys;
    }

    /**
     * @param int $mustParamKeys
     */
    public function setMustParamKeys(int $mustParamKeys): void
    {
        $this->mustParamKeys = $mustParamKeys;
    }

    /**
     * @return mixed
     */
    public function getDefaultOrder()
    {
        return $this->defaultOrder;
    }

    /**
     * @param int $defaultOrder
     */
    public function setDefaultOrder(int $defaultOrder): void
    {
        $this->defaultOrder = $defaultOrder;
    }

    /**
     * @return mixed
     */
    public function getMustParams()
    {
        return $this->mustParams;
    }

    /**
     * @param mixed $mustParams
     */
    public function setMustParams($mustParams): void
    {
        $this->mustParams = $mustParams;
    }

    /**
     * @return int|mixed
     */
    public function getDefaultFpi()
    {
        return $this->defaultFpi;
    }

}
