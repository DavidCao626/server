<?php
namespace App\EofficeApp\Calendar\Services;
use Eoffice;
/**
 * @日程模块服务类
 */
class CalendarAttentionService extends CalendarBaseService 
{

    private $calendarRepository;
    private $calendarSetRepository;
    protected $attachmentService;

    /**
     * @注册日程相关的资源库对象
     * @param \App\EofficeApp\Repositories\CalendarRepository $calendarRepository
     */
    public function __construct() 
    {
        parent::__construct();
        $this->calendarRepository = 'App\EofficeApp\Calendar\Repositories\CalendarRepository';
        $this->calendarSettingService = 'App\EofficeApp\Calendar\Services\CalendarSettingService';
        $this->calendarAttentionGroupRepository = 'App\EofficeApp\Calendar\Repositories\CalendarAttentionGroupRepository';
        $this->calendarSetRepository = 'App\EofficeApp\Calendar\Repositories\CalendarSetRepository';
        $this->calendarAttentionRepository = 'App\EofficeApp\Calendar\Repositories\CalendarAttentionRepository';
        $this->userSystemInfoRepository = 'App\EofficeApp\User\Repositories\UserSystemInfoRepository';
    }

    /**
     * 新增关注组
     * @param $param
     * @param $own
     * @return array
     */
    public function addAttentionGroup($param, $own) 
    {
        $param = $this->parseParams($param);
        $groupName = isset($param['groupName']) ? $param['groupName'] : null;
        $groupOrder = isset($param['groupOrder']) && is_numeric($param['groupOrder']) ? $param['groupOrder'] : 0;
        $userId = $own['user_id'];
        if (!$groupName)
            return ['code' => ['0x023002', 'archives']];
        $data = [
            'search' => [
                'user_id' => [$userId],
                'group_name' => [$groupName],
            ]
        ];
        if (app($this->calendarAttentionGroupRepository)->getAttentionGroupTotal($data) > 0)
            return ['code' => ['0x000020', 'common']];

        $data = [
            'user_id' => $userId,
            'group_name' => $groupName,
            'field_sort' => $groupOrder,
        ];
        return app($this->calendarAttentionGroupRepository)->insertData($data);
    }

    /**
     * 获取分组列表
     * @param $own
     */
    public function getAttentionGroupList($param, $own) 
    {
        $param = $this->parseParams($param);
        $param['search']['user_id'] = [$own['user_id']];
        if (isset($param['dataType']) && $param['dataType'] == 'all') {
            return app($this->calendarAttentionGroupRepository)->getAttentionGroup($param);
        }
        $data = $this->response(app($this->calendarAttentionGroupRepository), 'getAttentionGroupTotal', 'getAttentionGroup', $param);
        if (isset($param['from']) && $param['from'] == 'tree') {
            return $data['list'] ?? [];
        }
        return $data;
    }

    /**
     * 获取关注的分组信息
     * @param $group_id
     * @param $own
     * @return mixed
     */
    public function getAttentionGroupInfo($groupId, $userId)
    {
        if (is_array($userId)) {
            $userInfo = $userId;
            $userId = $userInfo['user_id'];
        }
        $param = [
            'search' => [
                'user_id' => [$userId],
                'group_id' => [$groupId]
            ],
            'type' => 'detail'
        ];
        return app($this->calendarAttentionGroupRepository)->getAttentionGroup($param);
    }

    /**
     * 保存关注分组信息
     * @param $groupId
     * @param $param
     * @param $own
     * @return array|bool
     */
    public function saveAttentionGroupInfo($groupId, $param, $userId) 
    {
        if (is_array($userId)) {
            $userInfo = $userId;
            $userId = $userInfo['user_id'];
        }
        $param = $this->parseParams($param);
        $groupName = isset($param['groupName']) ? $param['groupName'] : NULL;
        $fieldSort = isset($param['fieldSort']) ? $param['fieldSort'] : 0;
        if (!$groupName || !$groupId || !$userId)
            return ['code' => ['0x023002', 'archives']];

        $data = [
            'user_id' => $userId,
            'group_name' => $groupName,
            'field_sort' => $fieldSort,
        ];
        $where = [
            'group_id' => [$groupId],
            'user_id' => [$userId]
        ];
        //更新分组信息
        app($this->calendarAttentionGroupRepository)->updateData($data, $where);
        //获取该用户所有的分组
        $allGroups = app($this->calendarAttentionGroupRepository)->getAttentionGroup(['search' => ['user_id' => [$userId]]]);
        $groupIds = [];
        foreach ($allGroups as $val) {
            $groupIds[] = $val['group_id'];
        }
        //分组中新用户

        $groupUsers = isset($param['users']) && is_array($param['users']) ? $param['users'] : [];
        return  $this->groupAddAttentionUser($groupUsers, $groupId, $userId);
    }
    // 通过分组进行关注用户
    public function groupAddAttentionUser($groupUsers, $groupId, $userId) 
    {

        //获取关注表里改分组的数据
        $allGroupUser = app($this->calendarAttentionRepository)->getAllGroupUser($groupId, $userId);
        $updateWhere = [
            'group_id' => [$groupId],
        ];
        app($this->calendarAttentionRepository)->updateData(["group_id" => null], $updateWhere);
        // 把多余的数据插入关注表中
        $input = ['attention_to_person' => $groupUsers, 'group_id' => $groupId];
        if (!empty($input['attention_to_person'])) {
            foreach ($input['attention_to_person'] as $key => $value) {
                $updateWhere = [
                    'attention_person' => [$userId],
                    'attention_to_person' => [$groupUsers, 'in']
                ];
                app($this->calendarAttentionRepository)->updateData(["group_id" => $groupId],$updateWhere);
            }
        }
        return true;
        
    }

    /**
     * 删除关注分组
     * @param $groupId
     * @param $own
     * @return mixed
     */
    public function deleteAttentionGroup($groupId, $userId) 
    {
        if (is_array($userId)) {
            $userInfo = $userId;
            $userId = $userInfo['user_id'];
        }
        $param = [
            'group_id' => [$groupId],
            'user_id' => [$userId]
        ];
        $updateWhere = [
            'attention_person' => [$userId],
            'group_id' => $groupId
        ];
        //删除分组信息
        $result = app($this->calendarAttentionGroupRepository)->deleteByWhere($param);
        if ($result) {
            //删除用户数据
            app($this->calendarAttentionRepository)->updateData(["group_id" => null], $updateWhere);
        }
        return $result;
    }

    /**
     * 获取用户的分组信息
     * @param $param
     * @param $own
     * @return array
     */
    public function getUsersAttentionGroupsInfo($param, $own) 
    {
        $param = $this->parseParams($param);
        $userIds = explode(",", $param['userIds']);
        $data = [];
        foreach ($userIds as $key => $userId) {
            if (is_string($userId)) {
                $groupsInfo = app($this->calendarAttentionGroupRepository)->getAttentionGroupByUserId($own['user_id'], $userId);
                $data[$key] = [
                    'groupsInfo' => $groupsInfo,
                    'userId' => $userId
                ];
            }
        }
        return $data;
    }

    /**
     * 新增关注分组用户
     * @param $param
     * @param $own
     * @return array
     */
    public function addAttentionGroupUser($param, $own) 
    {
        $param = $this->parseParams($param);
        if (isset($param['user_id']) && isset($param['group_id']) && is_numeric($param['group_id'])) {
            $attentionUserId = $param['user_id'];
            $groupId = $param['group_id'];
            $userId = $own['user_id'];
            $groupInfo = app($this->calendarAttentionGroupRepository)->getDetail($groupId);
            if ($groupInfo) {
                $groupInfo = $groupInfo->toArray();
                if (isset($groupInfo['user_id']) && $groupInfo['user_id'] == $userId) {
                    //添加关注信息
                    $insertData = [
                        "user_id" => $attentionUserId,
                        "group_id" => $groupId,
                    ];
                    // 更新关注表数据
                    return app($this->calendarAttentionRepository)->updateData(["group_id" => $groupId], ['attention_person' => $userId, 'attention_to_person' => $attentionUserId]);
                } else {
                    // 无权限
                    return ['code' => ['0x000006', 'common']];
                }
            }
        }
        // 数据异常 无group_id 或 无user_id  或 group_id 无效
        return ['code' => ['0x023002', 'archives']];
    }
    /**
     * /我的关注分组列表展示
     * @param  [type] $groupId
     * @param  [type] $own    
     * @return [type]  array       
     */
    public function getAttentionGroupUserList($groupId, $own)
    {
        if ($groupId < 0) {
            return ['code' => ['0x000006', 'common']];
        }
        $initdate = array("user_id" => $own['user_id'], "user_name" => $own['user_name'], "attention_id" => 0, "dept_name" => $own['dept_name'], 'from' => '2');
        if ($groupId == 0) {
            $param = [
                'user_id' => $own['user_id'],
                'withDept' => true,
                'no_group' => true,
                'getAll'   => true
            ];
            $data = app($this->calendarAttentionGroupRepository)->getAttentionGroup(['search' => ['user_id' => [$own['user_id']]]]);
            $noGroup = app($this->calendarAttentionRepository)->calendarAttentionList($param);
            $myAttention = [];
            if (!empty($noGroup)) {
                foreach ($noGroup as $v) {
                    $myAttention[] = [
                        'attention_to_person' => $v['attention_to_person'],
                        'user_name' => $v['user_attention_to_person'] ? $v['user_attention_to_person']['user_name'] : '',
                        'dept_name' => !empty($v['user_attention_to_person']['user_to_dept']) ? $v['user_attention_to_person']['user_to_dept'][0]['dept_name'] : '',
                        'from' => '2',
                        'attention_id' => $v['attention_id'],
                        'is_read'   => $v['is_read']
                    ];
                }
            }
            
            array_unshift($myAttention, $initdate);
            if ($data) {
                foreach ($data as $key => $value) {
                    if (isset($value['users']) && !empty($value['users'])) {
                        $data[$key]['has_children'] = 1;
                        foreach ($value['users'] as $k => $v) {
                            $data[$key]['users'][$k]['user_name'] = get_user_simple_attr($v['attention_to_person']);
                            $dept = app($this->userSystemInfoRepository)->getDeptIdByUserId($v['attention_to_person']);
                            $data[$key]['users'][$k]['dept_name'] = isset($dept[0]['dept_name']) ? $dept[0]['dept_name'] : '';
                            $data[$key]['users'][$k]['from'] = '2';
                            $data[$key]['users'][$k]['is_read'] = $v['is_read'];
                        }
                    }
                    if ($value['group_id'] == null) {
                        array_unshift($data[$key]['users'], $initdate);
                    }
                }
            }
            $initGroup = [];
            if (!empty($myAttention)) {
                $initGroup = [
                    "group_id" => 0,
                    "group_name" => trans('calendar.default_gruop'),
                    "attention_to_person" => $own['user_id'],
                    "field_sort" => 0,
                    "users" => $myAttention
                ];

            }

            // 获取我有权限的共享组
            $shareGroup = app($this->calendarSettingService)->getMyCalendarPurview([], $own);
            $data = array_merge($data, $shareGroup);
            array_unshift($data, $initGroup);
            return $data;
        }
        return true;
    }
    /**
     * 查询日程关注人
     *
     * @param  array  $param 查询条件
     *
     * @return array  查询结果或错误码
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    public function getDiaryAttentionList($param = [])
    {
        $param = $this->parseParams($param);
        $data = $this->response(app($this->calendarAttentionRepository), 'getTotal', 'calendarAttentionList', $param);
        if (isset($param['search']['attention_person']) && count($data['list']) > 0) {
            foreach ($data['list'] as $key => $val) {
                $attentionToPerson = $val['attention_to_person'];
                $groupsInfo = app($this->calendarAttentionGroupRepository)->getAttentionGroupByUserId($param['search']['attention_person'], $attentionToPerson);
                $data['list'][$key]['groupsInfo'] = $groupsInfo;
            }
        }
        return $data;
    }

    /**
     * 添加日程关注人
     *
     * @param  array  $input 要添加的数据
     *
     * @return int|array  返回结果或错误码
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    public function createDiaryAttention($input, $userId)
    {
        if (!is_array($input['attention_to_person'])) {
            $input['attention_to_person'] = explode(',', $input['attention_to_person']);
        }

        $attToPersons = array_filter($input['attention_to_person']);
        // 获取设置信息
        $setInfo = app($this->calendarSettingService)->getBaseSetInfo('calendar_attention_key');
        if (!empty($attToPersons)) {
            $num = 0;
            $total = 0;
            if ($setInfo == 1) {
                $data = [
                    'attention_person' => $userId,
                    'attention_status' => 1,
                    'group_id' => $input['group_id'] ?? null
                ];
                // 发送提醒
                $sendData = [
                    'toUser' => $attToPersons,
                    'fromUser' => own()['user_name']
                ];
                $this->sendNotify($sendData, 'schedule-payattention');
            } else {
                $data = [
                    'attention_person' => $userId,
                    'attention_status' => 2,
                    'group_id' => $input['group_id'] ?? null
                ];
            }

            foreach ($attToPersons as $attToPerson) {
                $total++;
                $data['attention_to_person'] = $attToPerson;
                $where = [
                    'attention_person' => [$data['attention_person']],
                    'attention_to_person' => [$data['attention_to_person']]
                ];

                if (app($this->calendarAttentionRepository)->getTotal(['search' => $where]) > 0) {
                    app($this->calendarAttentionRepository)->updateData($data, $where);
                } else {
                   if (app($this->calendarAttentionRepository)->insertData($data)) {
                        $num++;
                    } 
                }

                
            }

            if ($num > 0) {
                return $total === $num ? 1 : ['code' => ['0x008004', 'diary']];
            }
        }
        return ['code' => ['0x008003', 'diary']];
    }

    private function sendNotify($data, $remarkType) {
        $sendData['remindMark'] = $remarkType;
        $sendData['toUser'] = $data['toUser'];
        $sendData['contentParam'] = ['attentionUser' => $data['fromUser']];
        $sendData['stateParams'] = [];
        Eoffice::sendMessage($sendData);
    }
    /**
     * 更新日程关注人
     *
     * @param  int|array $attentionIds    关注表id,多个用逗号隔开
     * @param  int       $attentionStatus 关注状态
     *
     * @return int|array              返回结果或错误码
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    public function updateDiaryAttention($attentionIds, $attentionStatus, $userId) 
    {
        $data = ['attention_status' => $attentionStatus];

        if ($attentionIds == 'all') {
            $where = [
                'attention_to_person' => [$userId],
                'attention_status' => [1]
            ];
            $num = app($this->calendarAttentionRepository)->getTotal(['search' => $where]);

            if ($num == 0) {
                return ['code' => ['0x008005', 'diary']];
            }
        } else {
            $where = ['attention_id' => array_filter(explode(',', $attentionIds))];
        }

        if (app($this->calendarAttentionRepository)->updateData($data, $where)) {
            return true;
        }
        return ['code' => ['0x000003', 'common']];
    }

    /**
     * 删除日程关注人(主键)
     *
     * @param  int|array  $attentionId 关注表id,多个用逗号隔开
     *
     * @return int|array  返回结果或错误码
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    public function deleteDiaryAttention($attentionId, $userId) 
    {
        $attentionIds = array_filter(explode(',', $attentionId));
        if (is_string($attentionIds[0])) {
            $where = [
                'attention_to_person' => [$attentionIds[0]],
                'attention_person' => [$userId],
            ];
            if (app($this->calendarAttentionRepository)->deleteByWhere($where)) {
                return true;
            }
        }

        if (app($this->calendarAttentionRepository)->deleteById($attentionIds)) {
            return true;
        }
        return true;
    }

    /**
     * 取消日程关注人(主键)
     *
     * @param  int|array  $attentionId 关注表id,多个用逗号隔开
     *
     * @return int|array  返回结果或错误码
     *
     */
    public function cancelCalendarAttention($attentionUserId, $userId) 
    {
        $attentionIds = array_filter(explode(',', $attentionUserId));
        if (is_string($attentionIds[0])) {
            $where = [
                'attention_to_person' => [$attentionIds[0]],
                'attention_person' => [$userId],
            ];
            if (app($this->calendarAttentionRepository)->deleteByWhere($where)) {
                return true;
            }
        }
        return ['code' => ['0x000003', 'common']];
    }

    public function setAttentionRead($param, $userId) {
        // 更新一下关注表的状态, 全部标记为已读
        $attentionUserIds = [];
        if (isset($param['group_id'])) {
            
            $users = app($this->calendarAttentionRepository)->getAllGroupUser($param['group_id'], $userId);
            $attentionUserIds = array_column($users, 'attention_to_person');
        } else {
            $attentionUserIds = [$param['user_id'] ?? ''];
        }
        if ($attentionUserIds) {
            return app($this->calendarAttentionRepository)->updateAttentionUserRead($userId, $attentionUserIds);
        }
    }
}
