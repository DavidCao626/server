<?php

namespace App\EofficeApp\Project\NewServices\Managers;

use App\EofficeApp\Project\Entities\ProjectManagerEntity;
use App\EofficeApp\Project\NewServices\Managers\RolePermission\Bins\ApiBins\BaseApiBin;
use App\EofficeApp\Project\NewServices\Managers\RolePermission\Bins\FunctionPageApiBin;
use App\EofficeApp\Project\NewServices\Managers\RolePermission\Bins\FunctionPageBin;
use Illuminate\Support\Collection;
use Illuminate\Support\Arr;
class DataManager
{
    private static $_instance = null;
    private $apiParams;
    private $apiBin;
    private $functionPageBin;
    private $functionPageApiBin;
    private $curUserId;
    private $own = [];
    private $managerId;
    private $project;
    private $relationId; // api主要操作的对象id，数组
    private $relations; // api主要的操作对象，collection
    private $roleFunctionPageModels; // 对象功能也关联模型集合 应该暂时没用上
    private $projectRoleUserModels; // 项目角色用户关联模型集合

    private function __construct() {}

    // 用户id必须要
    public static function getIns($own = null, $isNew = false)
    {
        if (is_null(self::$_instance) || $isNew) {
            self::$_instance = new self();
            self::$_instance->setOwn($own);
            self::$_instance->setCurUserId($own['user_id']);
        }
        return self::$_instance;
    }

    public static function hasIns() {
        return !is_null(self::$_instance);
    }

    // 切换，特定地方使用
    public function toggleDataManager(DataManager $dataManager)
    {
        self::$_instance = $dataManager;
    }

    public function getWitFPIs()
    {
        $data = $this->getApiParams('@with_fpis', []);
        if (is_string($data)) {
            $data = explode(',', $data);
        }
        return $data;
    }

    ###########get、set函数#########

    /**
     * @param string|array $key
     * @param mixed $default
     * @return mixed
     */
    public function getApiParams($key = null, $default = null)
    {
        return HelpersManager::arrayExtractWithNull($this->apiParams, $key, $default);
    }

    /**
     * @param mixed $apiParams
     */
    public function setApiParams($apiParams): void
    {
        $this->apiParams = $apiParams;
    }

    public function forgetApiParams($key)
    {
        $keys = HelpersManager::scalarToArray($key);
        foreach ($keys as $key) {
            unset($this->apiParams[$key]);
        }
    }

    public function setInApiParams($key, $value)
    {
        Arr::set($this->apiParams, $key, $value);
    }

    /**
     * @return mixed|BaseApiBin
     */
    public function getApiBin()
    {
        return $this->apiBin;
    }

    /**
     * @param mixed $apiBin
     */
    public function setApiBin($apiBin): void
    {
        $this->apiBin = $apiBin;
    }

    /**
     * @return mixed|FunctionPageBin
     */
    public function getFunctionPageBin()
    {
        return $this->functionPageBin;
    }

    /**
     * @param mixed $functionPageBin
     */
    public function setFunctionPageBin($functionPageBin): void
    {
        $this->functionPageBin = $functionPageBin;
    }

    /**
     * @return mixed|FunctionPageApiBin
     */
    public function getFunctionPageApiBin()
    {
        return $this->functionPageApiBin;
    }

    /**
     * @param mixed $functionPageApiBin
     */
    public function setFunctionPageApiBin($functionPageApiBin): void
    {
        $this->functionPageApiBin = $functionPageApiBin;
    }

    /**
     * @return mixed
     */
    public function getCurUserId()
    {
        return Arr::get($this->own, 'user_id');
    }

    /**
     * @param mixed $curUserId
     */
    public function setCurUserId($curUserId): void
    {
        $this->curUserId = $curUserId;
    }

    /**
     * @return mixed
     */
    public function getManagerId()
    {
        return $this->managerId;
    }

    /**
     * @param mixed $managerId
     */
    public function setManagerId($managerId): void
    {
        if ($managerId) {

        }
        $this->managerId = $managerId;
    }

    /**
     * @param string $field
     * @return mixed|ProjectManagerEntity
     */
    public function getProject($field = null, $default = null)
    {
        if ($field) {
            return object_get($this->project, $field, $default);
        }
        return $this->project;
    }

    /**
     * @param mixed $project
     */
    public function setProject($project): void
    {
        $this->project = $project;
    }

    /**
     * @return mixed
     */
    public function getPrimaryObjectId()
    {
        return $this->primaryObjectId;
    }

    /**
     * @param mixed $primaryObjectId
     */
    public function setPrimaryObjectId($primaryObjectId): void
    {
        $this->primaryObjectId = $primaryObjectId;
    }

    /**
     * @return mixed
     */
    public function getPrimaryObject()
    {
        return $this->primaryObject;
    }

    /**
     * @param mixed $primaryObject
     */
    public function setPrimaryObject($primaryObject): void
    {
        $this->primaryObject = $primaryObject;
    }

    /**
     * @return mixed
     */
    public function getRelationId()
    {
        return $this->relationId;
    }

    /**
     * @param mixed $relationId
     */
    public function setRelationId($relationId): void
    {
        $this->relationId = $relationId;
    }

    /**
     * @return mixed
     */
    public function getRelations()
    {
        return $this->relations;
    }

    /**
     * @param mixed $relations
     */
    public function setRelations($relations): void
    {
        $this->relations = $relations;
    }

    /**
     * @return mixed|Collection
     */
    public function getRoleFunctionPageModels()
    {
        return $this->roleFunctionPageModels;
    }

    /**
     * @param Collection $roleFunctionPageModels
     */
    public function setRoleFunctionPageModels(Collection $roleFunctionPageModels): void
    {
        $this->roleFunctionPageModels = $roleFunctionPageModels;
    }

    /**
     * @return mixed|Collection
     */
    public function getProjectRoleUserModels()
    {
        return $this->projectRoleUserModels;
    }

    /**
     * @param Collection $projectRoleUserModels
     */
    public function setProjectRoleUserModels(Collection $projectRoleUserModels): void
    {
        $this->projectRoleUserModels = $projectRoleUserModels;
    }

    /**
     * @return mixed
     */
    public function getOwn()
    {
        return $this->own;
    }

    /**
     * @param mixed $own
     */
    public function setOwn($own): void
    {
        $this->own = $own;
    }



}
