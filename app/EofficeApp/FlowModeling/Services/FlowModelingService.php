<?php

namespace App\EofficeApp\FlowModeling\Services;

use App\EofficeApp\Base\BaseService;
use Cache;
use DB;
use Lang;

/**
 * 流程建模
 *
 * @author: 缪晨晨
 *
 * @since：2018-02-28
 */
class FlowModelingService extends BaseService
{

    public function __construct()
    {
        $this->flowModelingRepository = 'App\EofficeApp\FlowModeling\Repositories\FlowModelingRepository';
        $this->userRepository         = 'App\EofficeApp\User\Repositories\UserRepository';
        $this->menuRepository         = 'App\EofficeApp\Menu\Repositories\MenuRepository';
    }

    /**
     * 获取模块树
     *
     * @author 缪晨晨
     *
     * @param  string $moduleParent [节点ID，默认0]
     *
     * @since  2018-02-28 创建
     *
     * @return array     返回结果
     */
    public function getFlowModuleTree($moduleParent)
    {
        $moduleParent = intval($moduleParent);
        $where        = [
            "module_parent" => [$moduleParent],
        ];
        $trees = app($this->flowModelingRepository)->getFlowModuleTree($where);
        return $trees;
    }

    /**
     * 获取模块列表
     *
     * @author 缪晨晨
     *
     * @param
     *
     * @since  2018-02-28 创建
     *
     * @return array
     */
    public function getFlowModuleList()
    {
        $where = [
            "module_parent" => [0],
        ];
        $moduleListInfo = app($this->flowModelingRepository)->getFlowModuleTree($where);
        return $moduleListInfo;
    }

    /**
     * 查询模块树(提供给选择器)
     *
     * @author 缪晨晨
     *
     * @param array $params 查询参数
     *
     * @since  2018-02-28 创建
     *
     * @return array
     */
    public function searchFlowModuleTreeForSelector($params)
    {
        $params = $this->parseParams($params);
        $result = app($this->flowModelingRepository)->searchFlowModuleTree($params);
        return $result;
    }

    /**
     * 获取模块信息
     *
     * @author 缪晨晨
     *
     * @param  string $moduleId [模块ID]
     *
     * @since  2018-02-28 创建
     *
     * @return array     返回结果
     */
    public function getFLowModuleInfoByModuleId($moduleId)
    {
        $parentModuleInfo = app($this->flowModelingRepository)->getDetail($moduleId);
        $data             = [];
        if (!empty($parentModuleInfo)) {
            $sonModuleInfo       = app($this->flowModelingRepository)->getModuleByWhere(['module_parent' => [$moduleId]]);
            $data['module_id']   = $parentModuleInfo->module_id;
            $data['module_name'] = $parentModuleInfo->module_name;
            $data['flow_id']     = $parentModuleInfo->state_url;
            $flowMenuRelation    = [
                2   => 'new',
                3   => 'toDo',
                5   => 'search',
                252 => 'already',
                323 => 'finished',
                420 => 'myRequest',
                521 => 'report',
            ];
            if (!empty($sonModuleInfo)) {
                foreach ($sonModuleInfo as $key => $value) {
                    $data[$flowMenuRelation[$value['module_type']]]['module_id']      = $value['module_id'];
                    $data[$flowMenuRelation[$value['module_type']]]['module_name']    = $value['module_name'];
                    $data[$flowMenuRelation[$value['module_type']]]['module_parent']  = $value['module_parent'];
                    $unserialize                                                      = unserialize($value['module_param']);
                    if (isset($unserialize["flowModuleInfo"]) && !empty($unserialize["flowModuleInfo"])) {
                        $data[$flowMenuRelation[$value['module_type']]]['flowModuleInfo'] = $unserialize["flowModuleInfo"];
                    }
                }
            }
        } else {
            return ['code' => ['0x000006', 'common']];
        }

        return $data;
    }

    /**
     * 添加模块
     *
     * @author 缪晨晨
     *
     * @param  array $data [模块数据]
     *
     * @since  2018-02-28 创建
     *
     * @return string or boolean   新建后的模块ID
     */
    public function addFlowModule($data)
    {
        $mainModuleData = [];
        if (!isset($data['flow_id']) || empty($data['flow_id'])) {
            return ['code' => ['0x203003', 'flowmodeling']];
        }
        if (!isset($data['module_name']) || empty($data['module_name'])) {
            return ['code' => ['0x203004', 'flowmodeling']];
        }
        // 检测模块名称重复
        $chechModuleInfo = app($this->flowModelingRepository)->getModuleByWhere(['module_name' => [$data['module_name']], 'module_type' => [0]]);
        if (!empty($chechModuleInfo)) {
            return ['code' => ['0x203005', 'flowmodeling']];
        }
        $mainModuleData['module_name']    = $data['module_name'];
        $moduleNamePyArray                = convert_pinyin($data['module_name']);
        $mainModuleData['module_name_zm'] = $moduleNamePyArray[1];
        $mainModuleData['module_name_py'] = $moduleNamePyArray[0];
        $mainModuleData['state_url']      = $data['flow_id'];
        $mainModuleData['module_parent']  = 0;
        // 插入主模块信息
        $moduleInfo = app($this->flowModelingRepository)->insertData($mainModuleData);
        // 插入子模块信息
        if ($moduleInfo) {
            $moduleParentId = $moduleInfo->module_id;
            // 此处默认模块名需要多语言
            $toDoModuleData      = $this->getTempModuleData($data, 'toDo', 3, $moduleParentId, '待办事宜');
            $alreadyModuleData   = $this->getTempModuleData($data, 'already', 252, $moduleParentId, '已办事宜');
            $finishedModuleData  = $this->getTempModuleData($data, 'finished', 323, $moduleParentId, '办结事宜');
            $myRequestModuleData = $this->getTempModuleData($data, 'myRequest', 420, $moduleParentId, '我的请求');
            $newModuleData       = $this->getTempModuleData($data, 'new', 2, $moduleParentId, '新建流程');
            $searchModuleData    = $this->getTempModuleData($data, 'search', 5, $moduleParentId, '流程查询');
            $reportModuleData    = $this->getTempModuleData($data, 'report', 521, $moduleParentId, '查看报表');
            $insertModuleData    = [
                $toDoModuleData,
                $alreadyModuleData,
                $finishedModuleData,
                $myRequestModuleData,
                $newModuleData,
                $searchModuleData,
                $reportModuleData,
            ];
            $insertModuleInfoResult = app($this->flowModelingRepository)->insertMultipleData($insertModuleData);
            return $insertModuleInfoResult ? 1 : false;
        } else {
            return false;
        }
    }

    // 组装模块数据
    public function getTempModuleData($data, $key, $menuId, $moduleParentId, $defaultModuleName = "")
    {
        $tempData                   = [];
        $data[$key]['module_name']  = isset($data[$key]['module_name']) && !empty($data[$key]['module_name']) ? $data[$key]['module_name'] : $defaultModuleName;
        $tempData['module_name']    = $data[$key]['module_name'];
        $moduleNamePyArray          = convert_pinyin($data[$key]['module_name']);
        $tempData['module_name_zm'] = $moduleNamePyArray[1];
        $tempData['module_name_py'] = $moduleNamePyArray[0];
        $tempData['module_parent']  = $moduleParentId;
        $tempData['module_type']    = $menuId;
        $stateUrl                   = [
            'type'    => 'flow',
            'params'  => [
                'flow_id' => $data['flow_id'],
            ],
            'menu_id' => $menuId,
        ];
        if ($menuId == '2') {
            $stateUrl = [
                'type'    => 'flow',
                'params'  => [
                    'flow_id'        => $data['flow_id'],
                    'run_id'         => '',
                    'history_run_id' => '',
                ],
                'menu_id' => $menuId,
            ];

        }
        $tempData['state_url']    = json_encode($stateUrl);
        $url                      = isset($data['flow_id']) ? $data['flow_id'] : "";
        $method                   = isset($data['flow_type']) ? $data['flow_type'] : 1;
        $flowModuleInfo           = isset($data[$key]['flowModuleInfo']) ? $data[$key]['flowModuleInfo'] : "";
        $tempData['module_param'] = serialize([
            'url'            => $url,
            'method'         => $method,
            'flowModuleInfo' => $flowModuleInfo,
        ]);

        return $tempData;
    }

    /**
     * 删除模块
     *
     * @author 缪晨晨
     *
     * @param  string $moduleId [模块ID]
     *
     * @since  2018-02-28 创建
     *
     * @return boolean
     */
    public function deleteFlowModule($moduleId)
    {
        // 检查模块子项是否有被在使用
        $moduleWhere = ['module_parent' => [$moduleId]];
        $sonModuleInfo = app($this->flowModelingRepository)->getModuleByWhere($moduleWhere);
        if (!empty($sonModuleInfo)) {
            $moduleIdArray = [];
            foreach ($sonModuleInfo as $key => $value) {
                $moduleIdArray[] = $value['module_id'];
            }
            if (!empty($moduleIdArray)) {
                $menuWhere    = ['menu_param' => [$moduleIdArray, 'in']];
                $menuData = app($this->menuRepository)->getMenuByWhere($menuWhere);
                if (!empty($menuData)) {
                    return ['code' => ['0x203002', 'flowmodeling']];
                }
            }
        }

        $moduleWhere = ['module_parent' => [$moduleId]];
        app($this->flowModelingRepository)->deleteByWhere($moduleWhere);

        return app($this->flowModelingRepository)->deleteById($moduleId);
    }

    /**
     * 编辑模块
     *
     * @author 缪晨晨
     *
     * @param  string $moduleId [模块ID], array $data [模块数据]
     *
     * @since  2018-02-28 创建
     *
     * @return boolean
     */
    public function editFlowModule($moduleId, $data)
    {
        if (!isset($data['flow_id']) || empty($data['flow_id'])) {
            return ['code' => ['0x203003', 'flowmodeling']];
        }
        if (!isset($data['module_name']) || empty($data['module_name'])) {
            return ['code' => ['0x203004', 'flowmodeling']];
        }
        // 检测模块名称重复
        $chechModuleInfo = app($this->flowModelingRepository)->getModuleByWhere(['module_name' => [$data['module_name']], 'module_id' => [$moduleId, '!='], 'module_type' => [0]]);
        if (!empty($chechModuleInfo)) {
            return ['code' => ['0x203005', 'flowmodeling']];
        }
        $moduleNamePyArray           = convert_pinyin($data['module_name']);
        $finalData['module_name']    = $data['module_name'];
        $finalData['module_name_zm'] = $moduleNamePyArray[1];
        $finalData['module_name_py'] = $moduleNamePyArray[0];
        $finalData['state_url']      = $data['flow_id'];
        $updateResult                = app($this->flowModelingRepository)->updateData($finalData, ['module_id' => $moduleId]);

        $toDoModuleData      = $this->getTempModuleData($data, 'toDo', 3, $moduleId, '待办事宜');
        $alreadyModuleData   = $this->getTempModuleData($data, 'already', 252, $moduleId, '已办事宜');
        $finishedModuleData  = $this->getTempModuleData($data, 'finished', 323, $moduleId, '办结事宜');
        $myRequestModuleData = $this->getTempModuleData($data, 'myRequest', 420, $moduleId, '我的请求');
        $newModuleData       = $this->getTempModuleData($data, 'new', 2, $moduleId, '新建流程');
        $searchModuleData    = $this->getTempModuleData($data, 'search', 5, $moduleId, '流程查询');
        $reportModuleData    = $this->getTempModuleData($data, 'report', 521, $moduleId, '查看报表');

        app($this->flowModelingRepository)->updateData($toDoModuleData, ['module_id' => $data['toDo']['module_id']]);
        app($this->flowModelingRepository)->updateData($alreadyModuleData, ['module_id' => $data['already']['module_id']]);
        app($this->flowModelingRepository)->updateData($finishedModuleData, ['module_id' => $data['finished']['module_id']]);
        app($this->flowModelingRepository)->updateData($myRequestModuleData, ['module_id' => $data['myRequest']['module_id']]);
        app($this->flowModelingRepository)->updateData($newModuleData, ['module_id' => $data['new']['module_id']]);
        app($this->flowModelingRepository)->updateData($searchModuleData, ['module_id' => $data['search']['module_id']]);
        app($this->flowModelingRepository)->updateData($reportModuleData, ['module_id' => $data['report']['module_id']]);

        // 清空用户菜单缓存
        app('App\EofficeApp\Menu\Services\UserMenuService')->clearCache();

        return $updateResult ? 1 : false;
    }
}
