<?php

namespace App\EofficeApp\System\Cas\Services;

use App\EofficeApp\Base\BaseService;
use App\Jobs\SyncCasJob;
use DB;
use Eoffice;
use Exception;
use Queue;
use Schema;

/**
 * cas Service类:提供cas相关服务
 *
 * @author 缪晨晨
 *
 * @since  2018-01-29 创建
 */
class CasService extends BaseService
{

    public function __construct()
    {
        parent::__construct();
        $this->casParamsRepository      = 'App\EofficeApp\System\Cas\Repositories\CasParamsRepository';
        $this->casSyncLogRepository     = 'App\EofficeApp\System\Cas\Repositories\CasSyncLogRepository';
        $this->userService              = 'App\EofficeApp\User\Services\UserService';
        $this->userSystemInfoRepository = 'App\EofficeApp\User\Repositories\UserSystemInfoRepository';
        $this->externalDatabaseServices = 'App\EofficeApp\System\ExternalDatabase\Services\ExternalDatabaseService';
    }

    /**
     * 【组织架构同步】 获取用户中间表字段列表
     *
     * @param
     *
     * @return array        查询结果
     *
     * @author 缪晨晨
     *
     * @since  2018-01-29 创建
     */
    public function getUserAssocFieldsList()
    {
        return [
            '0' => [
                'field_id'   => 'user_accounts',
                'field_name' => trans("cas.0x350003"), // '用户名',
                'database_table' => 'user',
            ],
            '1' => [
                'field_id'   => 'user_name',
                'field_name' => trans("cas.0x350004"), //'姓名',
                'database_table' => 'user',
            ],
            '2' => [
                'field_id'   => 'sex',
                'field_name' => trans("cas.0x350005"), //'性别', user_info表
                'database_table' => 'user_info',
            ],
            '3' => [
                'field_id'   => 'user_job_number',
                'field_name' => trans("cas.0x350006"), //'工号',
                'database_table' => 'user',
            ],
            '4' => [
                'field_id'   => 'dept_id',
                'field_name' => trans("cas.0x350007"), //'部门ID',
                'database_table' => 'user_system_info',
            ],
            '5' => [
                // 20191209-丁鹏-由mobile变更为phone_number(user_info表没有mobile字段)
                'field_id'   => 'phone_number',
                'field_name' => trans("cas.0x350008"), //'手机号码', user_info表
                'database_table' => 'user_info',
            ],
            '6' => [
                'field_id'   => 'email',
                'field_name' => trans("cas.0x350009"), //'邮箱', user_info表
                'database_table' => 'user_info',
            ],
        ];
    }

    /**
     * 【组织架构同步】 获取部门中间表字段列表
     *
     * @param
     *
     * @return array        查询结果
     *
     * @author 缪晨晨
     *
     * @since  2018-01-29 创建
     */
    public function getDepartmentAssocFieldsList()
    {
        return [
            '0' => [
                'field_id'   => 'source_dept_id',
                'field_name' => trans("cas.0x350007"), //'部门ID',
            ],
            '1' => [
                'field_id'   => 'source_dept_name',
                'field_name' => trans("cas.0x350011"), //'部门名称',
            ],
            '2' => [
                'field_id'   => 'source_parent_id',
                'field_name' => trans("cas.0x350012"), //'父级部门ID',
            ],
            '3' => [
                'field_id'   => 'source_dept_sort',
                'field_name' => trans("cas.0x350013"), //'部门序号',
            ],
            '4' => [
                'field_id'   => 'source_tel_no',
                'field_name' => trans("cas.0x350014"), //'部门电话',
            ],
            '5' => [
                'field_id'   => 'source_fax_no',
                'field_name' => trans("cas.0x350015"), //'部门传真',
            ],
        ];
    }

    /**
     * 【组织架构同步】 获取人事档案中间表字段列表
     *
     * @param
     *
     * @return array        查询结果
     *
     * @author 缪晨晨
     *
     * @since  2018-08-02 创建
     */
    public function getPersonnelFileAssocFieldsList()
    {
        $personnelFileFieldsList = app('App\EofficeApp\FormModeling\Services\FormModelingService')->listCustomFields(['lang' => true], 'personnel_files');
        return $personnelFileFieldsList;
    }

    /**
     * 【组织架构同步】 保存cas认证配置参数
     *
     * @param
     *
     * @return boolean
     *
     * @author 缪晨晨
     *
     * @since  2018-01-29 创建
     */
    public function saveCasParams($params)
    {
        // 必填判断等合并到语言包分支的时候再做
        if (isset($params['departmentDatabaseRelations'])) {
            if (!empty($params['departmentDatabaseRelations'])) {
                $fromFields        = '';
                $destinationFields = '';
                foreach ($params['departmentDatabaseRelations'] as $relations) {
                    if ($fromFields) {
                        $fromFields .= "," . $relations['databaseFields'];
                    } else {
                        $fromFields = $relations['databaseFields'];
                    }
                    if ($destinationFields) {
                        $destinationFields .= "," . $relations['assocFields'];
                    } else {
                        $destinationFields = $relations['assocFields'];
                    }
                }
                $params['dept_from_field']        = $fromFields;
                $params['dept_destination_field'] = $destinationFields;
            } else {
                $params['dept_from_field']        = '';
                $params['dept_destination_field'] = '';
            }
            unset($params['departmentDatabaseRelations']);
        }
        if (isset($params['userDatabaseRelations'])) {
            if (!empty($params['userDatabaseRelations'])) {
                $fromFields        = '';
                $destinationFields = '';
                foreach ($params['userDatabaseRelations'] as $relations) {
                    if ($fromFields) {
                        $fromFields .= "," . $relations['databaseFields'];
                    } else {
                        $fromFields = $relations['databaseFields'];
                    }
                    if ($destinationFields) {
                        $destinationFields .= "," . $relations['assocFields'];
                    } else {
                        $destinationFields = $relations['assocFields'];
                    }
                }
                $params['user_from_field']        = $fromFields;
                $params['user_destination_field'] = $destinationFields;
            } else {
                $params['user_from_field']        = '';
                $params['user_destination_field'] = '';
            }
            unset($params['userDatabaseRelations']);
        }
        if (isset($params['sync_personnel_file']) && $params['sync_personnel_file'] == 1) {
            if (isset($params['personnelFileDatabaseRelations'])) {
                if (!empty($params['personnelFileDatabaseRelations'])) {
                    $fromFields        = '';
                    $destinationFields = '';
                    foreach ($params['personnelFileDatabaseRelations'] as $relations) {
                        if ($fromFields) {
                            $fromFields .= "," . $relations['databaseFields'];
                        } else {
                            $fromFields = $relations['databaseFields'];
                        }
                        if ($destinationFields) {
                            $destinationFields .= "," . $relations['assocFields'];
                        } else {
                            $destinationFields = $relations['assocFields'];
                        }
                    }
                    $params['personnel_file_from_field']        = $fromFields;
                    $params['personnel_file_destination_field'] = $destinationFields;
                } else {
                    $params['personnel_file_from_field']        = '';
                    $params['personnel_file_destination_field'] = '';
                }
                unset($params['personnelFileDatabaseRelations']);
            }
        } else {
            $params['sync_personnel_file']              = 0;
            $params['personnel_file_from_field']        = '';
            $params['personnel_file_destination_field'] = '';
            $params['personnel_file_sync_basis_field']  = '';
            if (isset($params['personnelFileDatabaseRelations'])) {
                unset($params['personnelFileDatabaseRelations']);
            }
        }
        if (!empty($params)) {
            $data = [];
            foreach ($params as $key => $value) {
                if ($key == 'excluded_user' && is_array($value)) {
                    $value = implode(',', $value);
                }
                $data[] = [
                    'param_key'   => $key,
                    'param_value' => $value,
                ];
            }
            app($this->casParamsRepository)->truncateCasParamsTable();
            if (app($this->casParamsRepository)->insertMultipleData($data)) {
                return true;
            }
        }
        return false;
    }

    /**
     * 【组织架构同步】 获取cas认证配置参数
     *
     * @param
     *
     * @return array
     *
     * @author 缪晨晨
     *
     * @since  2018-01-29 创建
     */
    public function getCasParams()
    {
        $casParams = app($this->casParamsRepository)->getCasParams()->toArray();
        if (!empty($casParams)) {
            $params = [];
            foreach ($casParams as $key => $value) {
                if ($value['param_key'] == 'excluded_user' && !empty($value['param_value'])) {
                    $value['param_value'] = explode(',', $value['param_value']);
                }
                $params[$value['param_key']] = $value['param_value'];
            }
            $deptFromFields                           = isset($params['dept_from_field']) && !empty($params['dept_from_field']) ? explode(',', trim($params['dept_from_field'])) : '';
            $deptDestinationFields                    = isset($params['dept_destination_field']) && !empty($params['dept_destination_field']) ? explode(',', trim($params['dept_destination_field'])) : '';
            $userFromFields                           = isset($params['user_from_field']) && !empty($params['user_from_field']) ? explode(',', trim($params['user_from_field'])) : '';
            $userDestinationFields                    = isset($params['user_destination_field']) && !empty($params['user_destination_field']) ? explode(',', trim($params['user_destination_field'])) : '';
            $personnelFileFromFields                  = isset($params['personnel_file_from_field']) && !empty($params['personnel_file_from_field']) ? explode(',', trim($params['personnel_file_from_field'])) : '';
            $personnelFileDestinationFields           = isset($params['personnel_file_destination_field']) && !empty($params['personnel_file_destination_field']) ? explode(',', trim($params['personnel_file_destination_field'])) : '';
            $params['userDatabaseRelations']          = [];
            $params['departmentDatabaseRelations']    = [];
            $params['personnelFileDatabaseRelations'] = [];
            if (!empty($deptFromFields) && !empty($deptDestinationFields)) {
                for ($i = 0; $i < count($deptFromFields); $i++) {
                    if (empty($deptFromFields[$i]) || empty($deptDestinationFields[$i])) {
                        continue;
                    }
                    $params['departmentDatabaseRelations'][$i]['assocFields']         = $deptDestinationFields[$i];
                    $params['departmentDatabaseRelations'][$i]['databaseFields']      = $deptFromFields[$i];
                    $params['departmentDatabaseRelations'][$i]['databaseFieldsTitle'] = $deptFromFields[$i];
                    if ($deptDestinationFields[$i] == 'source_dept_id') {
                        $params['departmentDatabaseRelations'][$i]['assocFieldsTitle'] = trans("cas.0x350007"); //'部门ID';
                    }
                    if ($deptDestinationFields[$i] == 'source_dept_name') {
                        $params['departmentDatabaseRelations'][$i]['assocFieldsTitle'] = trans("cas.0x350011"); //'部门名称';
                    }
                    if ($deptDestinationFields[$i] == 'source_parent_id') {
                        $params['departmentDatabaseRelations'][$i]['assocFieldsTitle'] = trans("cas.0x350012"); //'父级部门ID';
                    }
                    if ($deptDestinationFields[$i] == 'source_dept_sort') {
                        $params['departmentDatabaseRelations'][$i]['assocFieldsTitle'] = trans("cas.0x350013"); //'部门序号';
                    }
                    if ($deptDestinationFields[$i] == 'source_tel_no') {
                        $params['departmentDatabaseRelations'][$i]['assocFieldsTitle'] = trans("cas.0x350014"); //'部门电话';
                    }
                    if ($deptDestinationFields[$i] == 'source_fax_no') {
                        $params['departmentDatabaseRelations'][$i]['assocFieldsTitle'] = trans("cas.0x350015"); //'部门传真';
                    }
                }
            }
            if (!empty($userFromFields) && !empty($userDestinationFields)) {
                for ($i = 0; $i < count($userFromFields); $i++) {
                    if (empty($userFromFields[$i]) || empty($userDestinationFields[$i])) {
                        continue;
                    }
                    $params['userDatabaseRelations'][$i]['assocFields']         = $userDestinationFields[$i];
                    $params['userDatabaseRelations'][$i]['databaseFields']      = $userFromFields[$i];
                    $params['userDatabaseRelations'][$i]['databaseFieldsTitle'] = $userFromFields[$i];
                    if ($userDestinationFields[$i] == 'user_accounts') {
                        $params['userDatabaseRelations'][$i]['assocFieldsTitle'] = trans("cas.0x350003"); //'用户名';
                    }
                    if ($userDestinationFields[$i] == 'user_name') {
                        $params['userDatabaseRelations'][$i]['assocFieldsTitle'] = trans("cas.0x350004"); //'姓名';
                    }
                    if ($userDestinationFields[$i] == 'sex') {
                        $params['userDatabaseRelations'][$i]['assocFieldsTitle'] = trans("cas.0x350005"); //'性别';
                    }
                    if ($userDestinationFields[$i] == 'user_job_number') {
                        $params['userDatabaseRelations'][$i]['assocFieldsTitle'] = trans("cas.0x350006"); //'工号';
                    }
                    if ($userDestinationFields[$i] == 'dept_id') {
                        $params['userDatabaseRelations'][$i]['assocFieldsTitle'] = trans("cas.0x350007"); //'部门ID';
                    }
                    if ($userDestinationFields[$i] == 'phone_number') {
                        $params['userDatabaseRelations'][$i]['assocFieldsTitle'] = trans("cas.0x350008"); //'手机号码';
                    }
                    if ($userDestinationFields[$i] == 'email') {
                        $params['userDatabaseRelations'][$i]['assocFieldsTitle'] = trans("cas.0x350009"); //'邮箱';
                    }
                }
            }

            $personnelFileFieldsList       = app('App\EofficeApp\FormModeling\Services\FormModelingService')->listCustomFields(['lang' => true], 'personnel_files');
            $personnelFileFieldIdNameArray = [];
            if (!empty($personnelFileFieldsList)) {
                foreach ($personnelFileFieldsList as $key => $value) {
                    if (isset($value->field_code) && isset($value->field_name)) {
                        $personnelFileFieldIdNameArray[$value->field_code] = $value->field_name;
                    }
                }
            }
            if (!empty($personnelFileFromFields) && !empty($personnelFileDestinationFields) && !empty($personnelFileFieldIdNameArray)) {
                for ($i = 0; $i < count($personnelFileFromFields); $i++) {
                    if (empty($personnelFileFromFields[$i]) || empty($personnelFileDestinationFields[$i])) {
                        continue;
                    }
                    $params['personnelFileDatabaseRelations'][$i]['assocFields']         = $personnelFileDestinationFields[$i];
                    $params['personnelFileDatabaseRelations'][$i]['assocFieldsTitle']    = isset($personnelFileFieldIdNameArray[$personnelFileDestinationFields[$i]]) ? $personnelFileFieldIdNameArray[$personnelFileDestinationFields[$i]] : '';
                    $params['personnelFileDatabaseRelations'][$i]['databaseFields']      = $personnelFileFromFields[$i];
                    $params['personnelFileDatabaseRelations'][$i]['databaseFieldsTitle'] = $personnelFileFromFields[$i];

                }
            }
            if (isset($params['sync_personnel_file'])) {
                $params['sync_personnel_file'] = intval($params['sync_personnel_file']);
            }
            return $params;
        } else {
            return 0;
        }
    }

    /**
     * 【组织架构同步】 更新部门路径
     *
     * @param
     *
     * @return boolean
     *
     * @author 缪晨晨
     *
     * @since  2018-01-29 创建
     */
    public function UpdateSortLevel($sortParentID, $temp = 0)
    {
        $result = DB::table('department')->where('parent_id', $sortParentID)->get();
        if (!empty($result)) {
            foreach ($result->toArray() as $key => $value) {
                $sortID      = $value->dept_id;
                $sortLevelID = 0;
                if ($sortParentID == '0') {
                    DB::table('department')->where('dept_id', $sortID)->update(['arr_parent_id' => '0']);
                }
                if ($sortParentID) {
                    $sortLevelID = $temp . ',' . $sortParentID;
                    DB::table('department')->where('dept_id', $sortID)->update(['arr_parent_id' => $sortLevelID]);
                }
                $this->UpdateSortLevel($sortID, $sortLevelID);
            }
        }
        return true;
    }

    /**
     * 【组织架构同步】 队列同步组织架构数据（用户和部门）
     *
     * @param
     *
     * @return boolean
     *
     * @author 缪晨晨
     *
     * @since  2018-01-29 创建
     */
    public function syncOrganizationData($params = [])
    {
        Queue::push(new SyncCasJob($params));
        // (new SyncCasJob($params))->handle();
        return true;
    }

    /**
     * 【组织架构同步】 同步组织架构数据（用户和部门）
     *
     * @param
     *
     * @return boolean
     *
     * @author 缪晨晨
     *
     * @since  2018-01-29 创建
     */
    public function syncCasOrganizationData($params = [])
    {
        $logData               = [];
        $logData['begin_time'] = date('Y-m-d H:i:s');

        // 检查系统是否开启了cas统一认证验证
        if (get_system_param('login_auth_type', '') != '3') {
            // 系统未开启CAS认证，请在系统管理性能安全设置中开启
            $logData['remark']      = trans('cas.0x350001');
            $logData['sync_status'] = 0;
            $logData['end_time']    = date('Y-m-d H:i:s');
            // 记录日志
            $this->saveCasSyncLog($logData);
            return false;
        }

        // 检查同步配置参数
        $casParams = $this->getCasParams();
        if (empty($casParams)) {
            // 参数为空，请先设置同步参数
            $logData['remark']      = trans("cas.0x350002");
            $logData['sync_status'] = 0;
            $logData['end_time']    = date('Y-m-d H:i:s');
            // 记录日志
            $this->saveCasSyncLog($logData);
            return false;
        }

        try {
            $excludedUser       = isset($casParams['excluded_user']) && !empty($casParams['excluded_user']) ? $casParams['excluded_user'] : [];
            $externalDatabaseId = isset($casParams['database_id']) ? $casParams['database_id'] : '';
            if (empty($externalDatabaseId)) {
                // 外部数据库ID不存在
                $logData['remark']      = trans("cas.0x350016");
                $logData['sync_status'] = 0;
                $logData['end_time']    = date('Y-m-d H:i:s');
                // 记录日志
                $this->saveCasSyncLog($logData);
                return false;
            }
            // 顶级部门标识
            $topDeptFlag                   = isset($casParams['top_dept_flag']) ? $casParams['top_dept_flag'] : '0';
            $sourceDeptTable               = isset($casParams['department_table_name']) ? $casParams['department_table_name'] : '';
            $sourceUserTable               = isset($casParams['user_table_name']) ? $casParams['user_table_name'] : '';
            $sourcePersonnelFileTable      = isset($casParams['personnel_file_table_name']) ? $casParams['personnel_file_table_name'] : '';
            $sourceDeptField               = isset($casParams['dept_from_field']) ? explode(',', trim($casParams['dept_from_field'])) : '';
            $sourceUserField               = isset($casParams['user_from_field']) ? explode(',', trim($casParams['user_from_field'])) : '';
            $sourcePersonnelFileField      = isset($casParams['personnel_file_from_field']) ? explode(',', trim($casParams['personnel_file_from_field'])) : '';
            $destinationDeptField          = isset($casParams['dept_destination_field']) ? explode(',', trim($casParams['dept_destination_field'])) : '';
            $destinationUserField          = isset($casParams['user_destination_field']) ? explode(',', trim($casParams['user_destination_field'])) : '';
            $destinationPersonnelFileField = isset($casParams['personnel_file_destination_field']) ? explode(',', trim($casParams['personnel_file_destination_field'])) : '';
            // 匹配字段关系
            $deptFieldRelation = [];
            if (!empty($sourceDeptField) && !empty($destinationDeptField) && (count($sourceDeptField) == count($destinationDeptField))) {
                $deptFieldRelation = [];
                foreach ($destinationDeptField as $key => $value) {
                    $deptFieldRelation[$value] = $sourceDeptField[$key];
                }
            }
            $userFieldRelation = [];
            if (!empty($sourceUserField) && !empty($destinationUserField) && (count($sourceUserField) == count($destinationUserField))) {
                $userFieldRelation = [];
                foreach ($destinationUserField as $key => $value) {
                    $userFieldRelation[$value] = $sourceUserField[$key];
                }
            }
            $personnelFileFieldRelation = [];
            if (isset($casParams['sync_personnel_file']) && $casParams['sync_personnel_file'] == '1') {
                if (!empty($sourcePersonnelFileField) && !empty($destinationPersonnelFileField) && (count($sourcePersonnelFileField) == count($destinationPersonnelFileField))) {
                    $personnelFileFieldRelation = [];
                    foreach ($destinationPersonnelFileField as $key => $value) {
                        $personnelFileFieldRelation[$value] = $sourcePersonnelFileField[$key];
                    }
                }
            }

            if (empty($deptFieldRelation) || empty($userFieldRelation) || (isset($casParams['sync_personnel_file']) && $casParams['sync_personnel_file'] == '1' && empty($personnelFileFieldRelation))) {
                // 字段匹配失败，请检查同步参数
                $logData['remark']      = trans("cas.0x350017");
                $logData['sync_status'] = 0;
                $logData['end_time']    = date('Y-m-d H:i:s');
                // 记录日志
                $this->saveCasSyncLog($logData);
                return false;
            }

            // ***********这里调外部数据库************还有查询到的中文转码
            $externalDatabase = app($this->externalDatabaseServices)->getExternalDatabasesConnect($externalDatabaseId);

            // 记录需要新增、更新和删除的ID
            $willInsertDeptIds          = [];
            $willUpdateDeptIds          = [];
            $willDeleteDeptIds          = [];
            $willInsertUserIds          = [];
            $willUpdateUserIds          = [];
            $willLeaveUserIds           = [];
            $willInsertPersonnelFileIds = [];
            $willUpdatePersonnelFileds  = [];

            if (!empty($sourceDeptTable)) {
                // 如果配置了部门关联关系才同同步部门
                $sourceDeptData   = $externalDatabase->table($sourceDeptTable)->select($sourceDeptField)->get()->toArray();
                $deptRelationData = DB::table('department_relation')->get()->toArray();
                $sourceDeptIds    = [];

                // 同步部门
                if (!empty($sourceDeptData) && isset($deptFieldRelation['source_dept_id'])) {
                    $sourceData             = [];
                    $willInsertRelationData = [];

                    foreach ($sourceDeptData as $key => $value) {
                        $sourceDeptIdKey = $deptFieldRelation['source_dept_id'];
                        if (!isset($value->$sourceDeptIdKey)) {
                            $sourceDeptIdKey = strtolower($sourceDeptIdKey);
                        }
                        $sourceDeptIds[]                      = $value->$sourceDeptIdKey;
                        $willInsertRelationData[]             = ['source_dept_id' => $value->$sourceDeptIdKey];
                        $sourceData[$value->$sourceDeptIdKey] = $value;
                    }

                    if (!empty($deptRelationData) && !empty($sourceDeptIds)) {
                        // 关联表已存在数据
                        foreach ($deptRelationData as $key => $value) {
                            if (in_array($value->source_dept_id, $sourceDeptIds)) {
                                // 需要更新的
                                $willUpdateDeptIds[] = $value->source_dept_id;
                            } else {
                                // 需要删除的
                                $willDeleteDeptIds[] = $value->dept_id;
                            }
                        }
                        if (!empty($willDeleteDeptIds)) {
                            DB::table('department')->whereIn('dept_id', $willDeleteDeptIds)->delete();
                            DB::table('department_relation')->whereIn('dept_id', $willDeleteDeptIds)->delete();
                        }
                        if (!empty($willUpdateDeptIds)) {
                            $willInsertDeptIds = array_diff($sourceDeptIds, $willUpdateDeptIds);
                            if (!empty($willInsertDeptIds)) {
                                $willInsertData = [];
                                foreach ($willInsertDeptIds as $key => $value) {
                                    $willInsertData[] = [
                                        'source_dept_id' => $value,
                                    ];
                                }
                                if (!empty($willInsertData)) {
                                    DB::table('department_relation')->insert($willInsertData);
                                }
                            }

                        }
                    } else {
                        // 首次插入
                        if (empty($deptRelationData) && !empty($willInsertRelationData)) {
                            DB::table('department_relation')->insert($willInsertRelationData);
                        }
                        $willInsertDeptIds = $sourceDeptIds;
                    }

                    $relationDeptData = DB::table('department_relation')->get()->toArray();
                    if (!empty($relationDeptData)) {
                        $relationKeyArray = [];
                        foreach ($relationDeptData as $key => $value) {
                            $relationKeyArray[$value->source_dept_id] = $value->dept_id;
                        }
                        if (!empty($relationKeyArray)) {
                            $tempSourceDeptData = $sourceDeptData;
                            foreach ($sourceDeptData as $key => $value) {
                                $sourceParentIdKey = $deptFieldRelation['source_parent_id'];
                                if (!isset($value->$sourceParentIdKey)) {
                                    $sourceParentIdKey = strtolower($sourceParentIdKey);
                                }
                                if (empty($value->$sourceParentIdKey) || $value->$sourceParentIdKey == $topDeptFlag) {
                                    $tempSourceDeptData[$key]->$sourceParentIdKey = 0;
                                } else {
                                    if (array_key_exists($value->$sourceParentIdKey, $relationKeyArray)) {
                                        $tempSourceDeptData[$key]->$sourceParentIdKey = $relationKeyArray[$value->$sourceParentIdKey];
                                    }
                                }
                            }

                            // 对OA部门表进行更新和新增
                            $willInsertData = [];
                            foreach ($tempSourceDeptData as $key => $value) {
                                $tempUpdateData = [];
                                if (isset($deptFieldRelation['source_dept_name'])) {
                                    $sourceDeptNameKey = $deptFieldRelation['source_dept_name'];
                                    if (!isset($value->$sourceDeptNameKey)) {
                                        $sourceDeptNameKey = strtolower($sourceDeptNameKey);
                                    }
                                    if (isset($value->$sourceDeptNameKey)) {
                                        $tempUpdateData['dept_name']    = transEncoding($value->$sourceDeptNameKey, 'UTF-8');
                                        $deptNamePyArray                = convert_pinyin($tempUpdateData['dept_name']);
                                        $deptNamePy                     = $deptNamePyArray[0];
                                        $deptNameZm                     = $deptNamePyArray[1];
                                        $tempUpdateData['dept_name_py'] = $deptNamePy;
                                        $tempUpdateData['dept_name_zm'] = $deptNameZm;
                                    }
                                }
                                if (isset($deptFieldRelation['source_dept_sort'])) {
                                    $sourceDeptSortKey = $deptFieldRelation['source_dept_sort'];
                                    if (!isset($value->$sourceDeptSortKey)) {
                                        $sourceDeptSortKey = strtolower($sourceDeptSortKey);
                                    }
                                    if (isset($value->$sourceDeptSortKey)) {
                                        $tempUpdateData['dept_sort'] = $value->$sourceDeptSortKey;
                                    }
                                }
                                if (isset($deptFieldRelation['source_parent_id'])) {
                                    $sourceParentIdKey = $deptFieldRelation['source_parent_id'];
                                    if (!isset($value->$sourceParentIdKey)) {
                                        $sourceParentIdKey = strtolower($sourceParentIdKey);
                                    }
                                    if (isset($value->$sourceParentIdKey)) {
                                        $tempUpdateData['parent_id'] = $value->$sourceParentIdKey;
                                    } else {
                                        $tempUpdateData['parent_id'] = '';
                                    }
                                }
                                if (isset($deptFieldRelation['source_tel_no'])) {
                                    $sourceTelNoKey = $deptFieldRelation['source_tel_no'];
                                    if (!isset($value->$sourceTelNoKey)) {
                                        $sourceTelNoKey = strtolower($sourceTelNoKey);
                                    }
                                    if (isset($value->$sourceTelNoKey)) {
                                        $tempUpdateData['tel_no'] = $value->$sourceTelNoKey;
                                    }
                                }
                                if (isset($deptFieldRelation['source_fax_no'])) {
                                    $sourceFaxNoKey = $deptFieldRelation['source_fax_no'];
                                    if (!isset($value->$sourceFaxNoKey)) {
                                        $sourceFaxNoKey = strtolower($sourceFaxNoKey);
                                    }
                                    if (isset($value->$sourceFaxNoKey)) {
                                        $tempUpdateData['fax_no'] = $value->$sourceFaxNoKey;
                                    }
                                }
                                if (!empty($willUpdateDeptIds)) {
                                    if (isset($deptFieldRelation['source_dept_id'])) {
                                        $sourceDeptIdKey = $deptFieldRelation['source_dept_id'];
                                        if (!isset($value->$sourceDeptIdKey)) {
                                            $sourceDeptIdKey = strtolower($sourceDeptIdKey);
                                        }
                                        $souceDeptId = $value->$sourceDeptIdKey;
                                        if (in_array($souceDeptId, $willUpdateDeptIds)) {
                                            DB::table('department')->where('dept_id', $relationKeyArray[$souceDeptId])->update($tempUpdateData);
                                            continue;
                                        }
                                    }
                                }
                                if (!empty($willInsertDeptIds)) {
                                    if (isset($deptFieldRelation['source_dept_id'])) {
                                        $sourceDeptIdKey = $deptFieldRelation['source_dept_id'];
                                        if (!isset($value->$sourceDeptIdKey)) {
                                            $sourceDeptIdKey = strtolower($sourceDeptIdKey);
                                        }
                                        $souceDeptId = $value->$sourceDeptIdKey;
                                        if (in_array($souceDeptId, $willInsertDeptIds)) {
                                            $tempUpdateData['dept_id'] = $relationKeyArray[$souceDeptId];
                                            $willInsertData[]          = $tempUpdateData;
                                        }
                                    }
                                }
                            }
                            if (!empty($willInsertData)) {
                                DB::table('department')->insert($willInsertData);
                            }
                        }
                    }
                    // 更新arr_parent_id字段
                    $this->UpdateSortLevel(0);
                    // 更新has_children字段
                    $deptTableData = DB::table('department')->get()->toArray();
                    foreach ($deptTableData as $key => $value) {
                        $checkHasChildren = DB::table('department')->where('parent_id', $value->dept_id)->first();
                        if (!empty($checkHasChildren)) {
                            DB::table('department')->where('dept_id', $value->dept_id)->update(['has_children' => 1]);
                        } else {
                            DB::table('department')->where('dept_id', $value->dept_id)->update(['has_children' => 0]);
                        }
                    }
                }
            } else {
                $relationKeyArray = [];
                $oaDepartmentData = DB::table('department')->get()->toArray();
                if (!empty($oaDepartmentData)) {
                    foreach ($oaDepartmentData as $key => $value) {
                        $relationKeyArray[$value->dept_id] = $value->dept_id;
                    }
                }
            }

            // 同步用户
            // 用户同步依据字段
            $syncBasisFieldKey = $casParams['sync_basis_field'];
            // 数据
            $sourceUserData = $externalDatabase->table($sourceUserTable)->select($sourceUserField)->get()->toArray();

            if (!empty($sourceUserData) && !empty($relationKeyArray)) {
                $sourceUserRelationKeyArray = [];
                if (isset($userFieldRelation[$syncBasisFieldKey])) {
                    $sourceUserRelationKey = $userFieldRelation[$syncBasisFieldKey];
                    foreach ($sourceUserData as $key => $value) {
                        if (!isset($value->$sourceUserRelationKey)) {
                            $sourceUserRelationKey = strtolower($sourceUserRelationKey);
                        }
                        if (isset($value->$sourceUserRelationKey)) {
                            $sourceUserRelationKeyArray[] = $value->$sourceUserRelationKey;

                            $syncBasisFieldKeyTable = $this->syncBasisFieldKeyTable($syncBasisFieldKey);
                            if($syncBasisFieldKeyTable == 'user') {
                                $checkHasUser = DB::table('user')->select(['user_id'])->where($syncBasisFieldKey, $value->$sourceUserRelationKey)->get()->first();
                            } else {
                                $checkHasUser = DB::table('user')->select(['user.user_id']);
                                $checkHasUser = $checkHasUser->leftJoin($syncBasisFieldKeyTable, $syncBasisFieldKeyTable.'.user_id', '=', 'user.user_id');
                                $checkHasUser = $checkHasUser->where($syncBasisFieldKeyTable.'.'.$syncBasisFieldKey, $value->$sourceUserRelationKey)->get()->first();
                            }

                            $tempUpdateData = [];

                            if (isset($userFieldRelation['dept_id']) && isset($relationKeyArray) && !empty($relationKeyArray)) {
                                $deptIdKey = $userFieldRelation['dept_id'];
                                if (!isset($value->$deptIdKey)) {
                                    $deptIdKey = strtolower($deptIdKey);
                                }
                                $tempUpdateData['dept_id'] = isset($relationKeyArray[$value->$deptIdKey]) ? $relationKeyArray[$value->$deptIdKey] : '';
                                if (empty($tempUpdateData['dept_id'])) {
                                    // 用户部门不存在的不更新，加入日志
                                    continue;
                                }
                            }

                            if (isset($userFieldRelation['user_name'])) {
                                $userNameKey = $userFieldRelation['user_name'];
                                if (!isset($value->$userNameKey)) {
                                    $userNameKey = strtolower($userNameKey);
                                }
                                if (isset($value->$userNameKey)) {
                                    $tempUpdateData['user_name'] = transEncoding($value->$userNameKey, 'UTF-8');
                                }
                            }
                            if (isset($userFieldRelation['user_accounts'])) {
                                $userAccountsKey = $userFieldRelation['user_accounts'];
                                if (!isset($value->$userAccountsKey)) {
                                    $userAccountsKey = strtolower($userAccountsKey);
                                }
                                if (isset($value->$userAccountsKey)) {
                                    $tempUpdateData['user_accounts'] = transEncoding($value->$userAccountsKey, 'UTF-8');
                                }
                            }
                            if (isset($userFieldRelation['sex'])) {
                                $sexKey = $userFieldRelation['sex'];
                                if (!isset($value->$sexKey)) {
                                    $sexKey = strtolower($sexKey);
                                }
                                if (isset($value->$sexKey)) {
                                    if (isset($casParams['male_sex_flag']) && isset($casParams['female_sex_flag'])) {
                                        $sexflag = [
                                            $casParams['male_sex_flag']   => 1,
                                            $casParams['female_sex_flag'] => 0,
                                        ];
                                    } else {
                                        $sexflag = [
                                            1 => 1,
                                            0 => 0,
                                        ];
                                    }
                                    $tempUpdateData['sex'] = $sexflag[$value->$sexKey];
                                }
                            }
                            if (isset($userFieldRelation['user_job_number'])) {
                                $jobNumberKey = $userFieldRelation['user_job_number'];
                                if (!isset($value->$jobNumberKey)) {
                                    $jobNumberKey = strtolower($jobNumberKey);
                                }
                                if (isset($value->$jobNumberKey)) {
                                    $tempUpdateData['user_job_number'] = transEncoding($value->$jobNumberKey, 'UTF-8');
                                }
                            }
                            if (isset($userFieldRelation['phone_number'])) {
                                $mobileKey = $userFieldRelation['phone_number'];
                                if (!isset($value->$mobileKey)) {
                                    $mobileKey = strtolower($mobileKey);
                                }
                                if (isset($value->$mobileKey)) {
                                    $tempUpdateData['phone_number'] = transEncoding($value->$mobileKey, 'UTF-8');
                                }
                            }
                            if (isset($userFieldRelation['email'])) {
                                $emailKey = $userFieldRelation['email'];
                                if (!isset($value->$emailKey)) {
                                    $emailKey = strtolower($emailKey);
                                }
                                if (isset($value->$emailKey)) {
                                    $tempUpdateData['email'] = transEncoding($value->$emailKey, 'UTF-8');
                                }
                            }

                            if ($checkHasUser) {
                                // 更新
                                $tempUpdateData['user_id'] = $checkHasUser->user_id;
                                $userStatus                = DB::table('user_system_info')->select(['user_status'])->where('user_id', $checkHasUser->user_id)->get()->first();
                                if ($userStatus) {
                                    if ($userStatus->user_status == '2') {
                                        // 如果OA中用户是离职状态，外部数据中有此用户，更新此用户为在职
                                        $userStatus->user_status = 1;
                                    }
                                    $tempUpdateData['user_status'] = $userStatus->user_status;
                                    $updateUserResult              = app($this->userService)->userSystemEdit($tempUpdateData);
                                    if (isset($updateUserResult['code'])) {
                                        if (isset($updateUserResult['code'][0]) && isset($updateUserResult['code'][1])) {
                                            $errorMessageContent = trans($updateUserResult['code'][1] . '.' . $updateUserResult['code'][0]);
                                            throw new Exception($errorMessageContent.';user_id:'.$checkHasUser->user_id);
                                        }
                                    }
                                    $willUpdateUserIds[] = 1;
                                }
                            } else {
                                // 新增
                                $tempUpdateData['user_password']     = isset($casParams['default_user_password']) ? $casParams['default_user_password'] : '';
                                $tempUpdateData['role_id_init'] = $casParams['default_user_role'];
                                $tempUpdateData['user_status']  = $casParams['default_user_status'];
                                $insertUserResult               = app($this->userService)->userSystemCreate($tempUpdateData);
                                if (isset($insertUserResult['code'])) {
                                    if (isset($insertUserResult['code'][0]) && isset($insertUserResult['code'][1])) {
                                        $errorMessageContent = trans($insertUserResult['code'][1] . '.' . $insertUserResult['code'][0]);
                                        throw new Exception($errorMessageContent.';insertData:'.json_encode($tempUpdateData));
                                    }
                                }
                                $willInsertUserIds[] = 1;
                            }
                        }
                    }
                }

                // 原来的获取数据，进行拆分，因为可能有不在user表的字段要查
                $oaUserData = $this->getOaUserData($syncBasisFieldKey, $excludedUser);
                if (!empty($oaUserData)) {
                    $willDeleteUserIdArray  = [];
                    foreach ($oaUserData as $key => $value) {
                        // 用户同步依据字段相应的值为空或者不在中间表的用户都设置为离职
                        if ($value->$syncBasisFieldKey === '' || !in_array($value->$syncBasisFieldKey, $sourceUserRelationKeyArray)) {
                            $willDeleteUserIdArray[] = $value->user_id;
                        }
                    }
                    if (!empty($willDeleteUserIdArray)) {
                        foreach ($willDeleteUserIdArray as $key => $value) {
                            // 不存在CAS认证中心的设为离职
                            $userInfo = app($this->userService)->getUserAllData($value);
                            if (!empty($userInfo)) {
                                if (isset($userInfo->user_name) && isset($userInfo->user_accounts)) {
                                    $tempEditUserData = [
                                        'user_id'       => $value,
                                        'user_name'     => $userInfo->user_name,
                                        'user_accounts' => $userInfo->user_accounts,
                                        'user_status'   => 2,
                                        'dept_id' => (isset($userInfo->userHasOneSystemInfo) && isset($userInfo->userHasOneSystemInfo->dept_id)) ? $userInfo->userHasOneSystemInfo->dept_id : '',
                                    ];
                                    $deleteUserResult = app($this->userService)->userSystemEdit($tempEditUserData);
                                    if (isset($deleteUserResult['code'])) {
                                        if (isset($deleteUserResult['code'][0]) && isset($deleteUserResult['code'][1])) {
                                            $errorMessageContent = trans($deleteUserResult['code'][1] . '.' . $deleteUserResult['code'][0]);
                                            throw new Exception($errorMessageContent.';user_name:'.$userInfo->user_name.';user_id:'.$value);
                                        }
                                    }
                                    $willLeaveUserIds[] = 1;
                                }
                            }
                        }

                    }
                }
            }

            // 同步人事档案
            if (isset($casParams['sync_personnel_file'])
                && $casParams['sync_personnel_file'] == '1'
                && !empty($sourcePersonnelFileTable)
                && !empty($sourcePersonnelFileField)
                && isset($casParams['personnel_file_sync_basis_field'])
                && !empty($casParams['personnel_file_sync_basis_field'])
                && isset($syncBasisFieldKey)
                && !empty($syncBasisFieldKey)) {
                $sourcePersonnelFileData = $externalDatabase->table($sourcePersonnelFileTable)->select($sourcePersonnelFileField)->get()->toArray();
                $syncBasisField          = $casParams['personnel_file_sync_basis_field'];
                if (!empty($sourcePersonnelFileData)) {
                    foreach ($sourcePersonnelFileData as $key => $value) {
                        if (!isset($value->$syncBasisField) || empty($value->$syncBasisField)) {
                            continue;
                        } else {
                            // 检测用户表中是否存在关联的用户信息

                            $syncBasisFieldKeyTable = $this->syncBasisFieldKeyTable($syncBasisFieldKey);
                            if($syncBasisFieldKeyTable == 'user') {
                                $checkRelateUserInfo = DB::table('user')->where($syncBasisFieldKey, $value->$syncBasisField)->first();
                            } else {
                                $checkRelateUserInfo = DB::table('user')->select(['user.user_id']);
                                $checkRelateUserInfo = $checkRelateUserInfo->leftJoin($syncBasisFieldKeyTable, $syncBasisFieldKeyTable.'.user_id', '=', 'user.user_id');
                                $checkRelateUserInfo = $checkRelateUserInfo->where($syncBasisFieldKeyTable.'.'.$syncBasisFieldKey, $value->$syncBasisField)->first();
                            }

                            $personnelFileData   = [];
                            if (!empty($checkRelateUserInfo) && isset($checkRelateUserInfo->user_id)) {
                                $personnelFileData['user_id'] = $checkRelateUserInfo->user_id;
                            }

                            foreach ($personnelFileFieldRelation as $rKey => $rValue) {
                                if (isset($value->$rValue)) {
                                    $personnelFileData[$rKey] = $value->$rValue;
                                }
                            }

                            $checkPersonnelFilesUserExist = DB::table('personnel_files')->where($syncBasisField, $value->$syncBasisField)->first();
                            if (!empty($checkPersonnelFilesUserExist)) {
                                DB::table('personnel_files')->where($syncBasisField, $value->$syncBasisField)->update($personnelFileData);
                                $willUpdatePersonnelFileds[] = 1;
                            } else {
                                DB::table('personnel_files')->insert($personnelFileData);
                                $willInsertPersonnelFileIds[] = 1;
                            }
                        }
                    }
                }
            }

            $logData['add_dept']              = count($willInsertDeptIds);
            $logData['update_dept']           = count($willUpdateDeptIds);
            $logData['delete_dept']           = count($willDeleteDeptIds);
            $logData['add_user']              = count($willInsertUserIds);
            $logData['update_user']           = count($willUpdateUserIds);
            $logData['leave_user']            = count($willLeaveUserIds);
            $logData['add_personnel_file']    = count($willInsertPersonnelFileIds);
            $logData['update_personnel_file'] = count($willUpdatePersonnelFileds);
            $logData['sync_status']           = 1;
        } catch (\Exception $e) {
            $logData['remark']      = $e->getMessage();
            $logData['sync_status'] = 0;
        }

        $logData['end_time'] = date('Y-m-d H:i:s');
        // 记录日志
        $this->saveCasSyncLog($logData);

        $sendData = [
            'toUser'      => isset($params['user_id']) ? $params['user_id'] : 'admin',
            'remindState' => 'cas.log',
            'remindMark'  => 'cas-complete',
            'sendMethod'  => ['sms'],
            'isHand'      => true,
            'content'     => trans("cas.0x350010"), // '您的CAS组织架构同步成功！',
            'stateParams' => [],
        ];
        Eoffice::sendMessage($sendData);

        return true;
    }

    /**
     * 原来的获取数据，进行拆分，因为可能有不在user表的字段要查
     * @param  [type] $syncBasisFieldKey [description]
     * @param  [type] $excludedUser      [description]
     * @return [type]                    [description]
     */
    public function getOaUserData($syncBasisFieldKey, $excludedUser) {
        // 匹配出 用户同步依据字段-$syncBasisFieldKey 属于哪个表
        $syncBasisFieldKeyTable = $this->syncBasisFieldKeyTable($syncBasisFieldKey);
        if($syncBasisFieldKeyTable == 'user') {
            $oaUserData = DB::table('user')->select([$syncBasisFieldKey, 'user_id'])->where('user_accounts', '!=', '')->where('user_id', '!=', 'admin');
        } else {
            $oaUserData = DB::table('user')->select([$syncBasisFieldKeyTable.'.'.$syncBasisFieldKey, 'user.user_id']);
            $oaUserData = $oaUserData->leftJoin($syncBasisFieldKeyTable, $syncBasisFieldKeyTable.'.user_id', '=', 'user.user_id');
            $oaUserData = $oaUserData->where('user_accounts', '!=', '')->where('user.user_id', '!=', 'admin');
        }
        if (!empty($excludedUser)) {
            // 排除的用户
            $oaUserData = $oaUserData->whereNotIn('user_id', $excludedUser);
        }
        $oaUserData = $oaUserData->get()->toArray();
        return $oaUserData;
    }

    /**
     * 匹配出 用户同步依据字段-$syncBasisFieldKey 属于哪个表
     * @param  [type] $syncBasisFieldKey [description]
     * @return [type]                    [description]
     */
    public function syncBasisFieldKeyTable($syncBasisFieldKey) {
        // 匹配出 用户同步依据字段-$syncBasisFieldKey 属于哪个表
        $userAssocFieldsList = $this->getUserAssocFieldsList();
        $syncBasisFieldKeyTable = 'user';
        foreach ($userAssocFieldsList as $key => $value) {
            if($value['field_id'] == $syncBasisFieldKey) {
                $syncBasisFieldKeyTable = $value['database_table'];
            }
        }
        return $syncBasisFieldKeyTable;
    }
    /**
     * 【组织架构同步】 记录同步日志（用户和部门）
     *
     * @param array $data
     *
     * @return boolean
     *
     * @author 缪晨晨
     *
     * @since  2018-01-29 创建
     */
    public function saveCasSyncLog($data)
    {
        if (!empty($data)) {
            $data = array_intersect_key($data, array_flip(Schema::getColumnListing('cas_sync_log')));
            return DB::table('cas_sync_log')->insert($data);
        }
    }

    /**
     * 【组织架构同步】 获取同步日志（用户和部门）
     *
     * @param
     *
     * @return array
     *
     * @author 缪晨晨
     *
     * @since  2018-01-29 创建
     */
    public function getCasSyncLog($params)
    {
        $params      = $this->parseParams($params);
        $syncLogList = $this->response(app($this->casSyncLogRepository), 'getCasSyncLogListTotal', 'getCasSyncLogList', $params);
        return $syncLogList;
    }
}
