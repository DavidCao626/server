<?php
namespace App\EofficeApp\Vote\Services;

use App\EofficeApp\Base\BaseService;
use DB;
use Schema;
use Eoffice;
use Illuminate\Support\Arr;
class VoteService extends BaseService
{

    public function __construct(
    ) {
        parent::__construct();
        $this->voteRepository                = 'App\EofficeApp\Vote\Repositories\VoteRepository';
        $this->voteDeptRepository            = 'App\EofficeApp\Vote\Repositories\VoteDeptRepository';
        $this->voteRoleRepository            = 'App\EofficeApp\Vote\Repositories\VoteRoleRepository';
        $this->voteUserRepository            = 'App\EofficeApp\Vote\Repositories\VoteUserRepository';
        $this->voteControlDesignerRepository = 'App\EofficeApp\Vote\Repositories\VoteControlDesignerRepository';
        $this->voteVersionRepository         = 'App\EofficeApp\Vote\Repositories\VoteVersionRepository';
        $this->voteModeRepository            = 'App\EofficeApp\Vote\Repositories\VoteModeRepository';
        $this->attachmentService             = 'App\EofficeApp\Attachment\Services\AttachmentService';
        $this->voteLogRepository             = 'App\EofficeApp\Vote\Repositories\VoteLogRepository';
    }

    /**
     * 获取调查表列表
     */
    public function getVoteManageList($params)
    {
        $param = $this->parseParams($params);
        $list  = app($this->voteRepository)->getVoteManageList($param);
        $count = app($this->voteRepository)->getVoteManageTotal($param);
        //获取已产生数据的投票id
        $readId = app($this->voteLogRepository)->getHasDataList();

        if($readId) {
            $readIds = $readId->pluck('vote_id')->toArray();
        }
        foreach ($list as $key => $value) {
            if(in_array($value['id'],$readIds)) {
                $list[$key]['hasData'] = 1;
            }else{
                $list[$key]['hasData'] = 0;
            }
        }
        return ['total' => $count, 'list' => $list];
    }
    /**
     * 获取调查表设置详情
     */
    public function getVoteManageInfo($voteId,$userInfo)
    {
        if (!$voteId) {
            return ['code' => ['0x249001', 'vote']];
        }
        $voteInfo = app($this->voteRepository)->showVote($voteId);
        if (!$voteInfo) {
            return ['code' => ['0x249001', 'vote']];
        }
        if(count($voteInfo->voteHasManyUser)){
            $voteInfo->vote_user = $voteInfo->voteHasManyUser->pluck("user_id");
        }
        if(count($voteInfo->voteHasManyRole)){
            $voteInfo->vote_role = $voteInfo->voteHasManyRole->pluck("role_id");
        }
        if(count($voteInfo->voteHasManyDept)){
            $voteInfo->vote_dept = $voteInfo->voteHasManyDept->pluck("dept_id");
        }
        $voteInfo->attachment_id = app($this->attachmentService)->getAttachmentIdsByEntityId(['entity_table' => 'vote_manage', 'entity_id' => $voteId]);
        //获取当前用户的历史数据
        $historyDate = [];
        $tableName = 'vote_data_'.$voteId;
        if (Schema::hasTable($tableName)) {
            $historyDate = DB::table($tableName)->where("user_id", $userInfo['user_id'])->where('vote_id',$voteId)->orderBy('created_at','desc')->first();
        }
        if(!empty($historyDate)) {
            $resultData = $this->getVoteInDetail($historyDate->id,['vote_id'=>$voteId],$userInfo);
            $historyDate = $resultData['data'];
        }

        $voteInfo->history_date = json_encode($historyDate);
        return $voteInfo;
    }
    /**
     * 获取调查表设置详情
     */
    public function getVoteManageInfoOutAttment($voteId)
    {
        if (!$voteId) {
            return ['code' => ['0x249001', 'vote']];
        }
        $voteInfo = app($this->voteRepository)->showVote($voteId);
        if(count($voteInfo->voteHasManyUser)){
            $voteInfo->vote_user = $voteInfo->voteHasManyUser->pluck("user_id");
        }
        if(count($voteInfo->voteHasManyRole)){
            $voteInfo->vote_role = $voteInfo->voteHasManyRole->pluck("role_id");
        }
        if(count($voteInfo->voteHasManyDept)){
            $voteInfo->vote_dept = $voteInfo->voteHasManyDept->pluck("dept_id");
        }
        return $voteInfo;
    }
    /**
     * 删除调查表设置详情
     */
    public function deleteVoteManage($voteId, $userInfo)
    {
        if (!$voteId) {
            return ['code' => ['0x249001', 'vote']];
        }
        $voteIds = explode(',', $voteId);
        //获取已产生数据的投票id
        $readId = app($this->voteLogRepository)->getHasDataList();

        if($readId) {
            $readIds = $readId->pluck('vote_id')->toArray();
        }
        $error = 0;
        foreach ($voteIds as  $value) {
            $voteManageInfo = app($this->voteRepository)->getDetail($value);
            if($voteManageInfo->active == 1 || in_array($value,$readIds)) {
                continue;
            }
            if (app($this->voteRepository)->deleteById($value)) {
                //删除参与人数据
                app($this->voteDeptRepository)->deleteByWhere(['vote_id' => [$value]]);
                app($this->voteRoleRepository)->deleteByWhere(['vote_id' => [$value]]);
                app($this->voteUserRepository)->deleteByWhere(['vote_id' => [$value]]);
                //删除调查表设计详情
                app($this->voteControlDesignerRepository)->deleteByWhere(['vote_id' => [$value]]);
                //删除数据
                $this->deleteAllVoteResult($value);
            }else{
                $error ++;
            }
        }
        if($error == 0 ) {
            return true;
        }
        return ['code' => ['0x000003', 'common']];
    }
    /**
     * 编辑调查表
     */
    public function editVoteManage($data, $voteId, $userInfo)
    {
        if (!$voteId) {
            return ['code' => ['0x249001', 'vote']];
        }
        $voteInfo = [
            'vote_name'          => $this->defaultValue('vote_name', $data,  trans('vote.default_name')),
            'vote_desc'          => $this->defaultValue('vote_desc', $data, ''),
            'start_time'         => $this->defaultValue('start_time', $data, '0000-00-00 00:00:00'),
            'end_time'           => $this->defaultValue('end_time', $data, '0000-00-00 00:00:00'),
            'anonymous'          => $this->defaultValue('anonymous', $data, 1),
            'multiple_submit'    => $this->defaultValue('multiple_submit', $data, 1),
            'open_result'        => $this->defaultValue('open_result', $data, 0),
            'active'             => $this->defaultValue('active', $data, 0),
            'after_login_open' => $this->defaultValue('after_login_open', $data, 1),
            'remind'             => $this->defaultValue('remind', $data, 1),
            'template'           => $this->defaultValue('template', $data, ''),
            'max_id'             => $this->defaultValue('max_id', $data, 0),
            'all_user'           => $this->defaultValue('all_user', $data, 1),
        ];
        if($voteInfo['start_time'] > $voteInfo['end_time']) {
            return ['code' => ['0x249007', 'vote']];
        }
        if($voteInfo['end_time'] <= date("Y-m-d H:i",time())) {
            return ['code' => ['0x249007', 'vote']];
        }

        if (app($this->voteRepository)->updateData($voteInfo, ['id' => $voteId])) {
            if (isset($data['attachment_id']) && $data['attachment_id'] != "") {
                app($this->attachmentService)->attachmentRelation("vote_manage", $voteId, $data['attachment_id']);
            }
            //删除原有数据
            app($this->voteDeptRepository)->deleteByWhere(['vote_id' => [$voteId]]);
            app($this->voteRoleRepository)->deleteByWhere(['vote_id' => [$voteId]]);
            app($this->voteUserRepository)->deleteByWhere(['vote_id' => [$voteId]]);
            if ($voteInfo['all_user'] == 0) {
                if (isset($data['dept_id']) && is_array($data['dept_id'])) {
                    $deptData = [];
                    foreach (array_filter($data['dept_id']) as $v) {
                        $deptData[] = ['vote_id' => $voteId, 'dept_id' => $v];
                    }
                    app($this->voteDeptRepository)->insertMultipleData($deptData);
                }
                if (isset($data['role_id']) && is_array($data['role_id'])) {
                    $roleData = [];
                    foreach (array_filter($data['role_id']) as $v) {
                        $roleData[] = ['vote_id' => $voteId, 'role_id' => $v];
                    }
                    app($this->voteRoleRepository)->insertMultipleData($roleData);
                }
                if (isset($data['user_id']) && is_array($data['user_id'])) {
                    $userData = [];
                    foreach (array_filter($data['user_id']) as $v) {
                        $userData[] = ['vote_id' => $voteId, 'user_id' => $v];
                    }
                    app($this->voteUserRepository)->insertMultipleData($userData);
                }
            }
            if ($voteInfo['active'] == 1) {
                //创建调查表数据分表
                $tableName = "vote_data_" . $voteId;
                if (!Schema::hasTable($tableName)) {
                    Schema::create($tableName, function ($table) {
                        $table->increments("id")->comment(trans("vote.increase_id"));
                        $table->integer("vote_id")->comment(trans("vote.vote_id"));
                        $table->string("user_id")->comment(trans("vote.vote_in_user_id"));
                        $table->tinyInteger("anonymous")->comment(trans("vote.is_anonymous"))->default(0);
                        $table->integer("version")->comment(trans("vote.vote_in_version_id"));

                        $table->timestamps();
                    });
                }
            }
            if ($voteInfo['remind'] == 1 && $voteInfo['start_time'] <= date('Y-m-d H:i') && $voteInfo['active'] == 1) {
                $sendData['remindMark']   = 'vote-submit';
                $userId                   = $this->getVoteInUsers($voteId);
                $sendData['toUser']       = implode(',', $userId);
                $sendData['contentParam'] = ['voteTitle' => $voteInfo['vote_name'], 'userName' => $userInfo['user_name']];
                $sendData['stateParams']  = ['vote_id' => $voteId];
                Eoffice::sendMessage($sendData);
            }
        } else {
            return ['code' => ['frequent_operation', 'common']];
        }
    }
    /**
     * 添加调查表
     */
    public function addVoteManage($data, $userInfo)
    {

        $voteInfo = [
            'vote_name'          => $this->defaultValue('vote_name', $data, trans('vote.default_name')),
            'vote_desc'          => $this->defaultValue('vote_desc', $data, ''),
            'start_time'         => $this->defaultValue('start_time', $data, '0000-00-00 00:00:00'),
            'end_time'           => $this->defaultValue('end_time', $data, '0000-00-00 00:00:00'),
            'anonymous'          => $this->defaultValue('anonymous', $data, 1),
            'multiple_submit'    => $this->defaultValue('multiple_submit', $data, 1),
            'open_result'        => $this->defaultValue('open_result', $data, 0),
            'active'             => $this->defaultValue('active', $data, 0),
            'after_login_open' => $this->defaultValue('after_login_open', $data, 1),
            'remind'             => $this->defaultValue('remind', $data, 1),
            'template'           => $this->defaultValue('template', $data, ''),
            'all_user'           => $this->defaultValue('all_user', $data, 1),
            'max_id'             => $this->defaultValue('max_id', $data, 0),
            'creator'            => $userInfo['user_id'],
        ];
        if($voteInfo['start_time'] > $voteInfo['end_time']) {
            return ['code' => ['0x249007', 'vote']];
        }
        if($voteInfo['end_time'] <= date("Y-m-d H:i",time())) {
            return ['code' => ['0x249007', 'vote']];
        }
        if ($result = app($this->voteRepository)->insertData($voteInfo)) {
            $voteId = $result->id;
            if (isset($data['attachment_id']) && $data['attachment_id'] != "") {
                app($this->attachmentService)->attachmentRelation("vote_manage", $voteId, $data['attachment_id']);
            }
            if ($voteInfo['all_user'] == 0) {
                if (isset($data['dept_id']) && is_array($data['dept_id'])) {
                    $deptData = [];
                    foreach (array_filter($data['dept_id']) as $v) {
                        $deptData[] = ['vote_id' => $voteId, 'dept_id' => $v];
                    }
                    app($this->voteDeptRepository)->insertMultipleData($deptData);
                }
                if (isset($data['role_id']) && is_array($data['role_id'])) {
                    $roleData = [];
                    foreach (array_filter($data['role_id']) as $v) {
                        $roleData[] = ['vote_id' => $voteId, 'role_id' => $v];
                    }
                    app($this->voteRoleRepository)->insertMultipleData($roleData);
                }
                if (isset($data['user_id']) && is_array($data['user_id'])) {
                    $userData = [];
                    foreach (array_filter($data['user_id']) as $v) {
                        $userData[] = ['vote_id' => $voteId, 'user_id' => $v];
                    }
                    app($this->voteUserRepository)->insertMultipleData($userData);
                }
            }
            if ($voteInfo['active'] == 1) {
                //创建调查表数据分表
                $tableName = "vote_data_" . $voteId;
                if (!Schema::hasTable($tableName)) {
                    Schema::create($tableName, function ($table) {
                        $table->increments("id")->comment(trans("vote.increase_id"));
                        $table->integer("vote_id")->comment(trans("vote.vote_id"));
                        $table->string("user_id")->comment(trans("vote.vote_in_user_id"));
                        $table->tinyInteger("anonymous")->comment(trans("vote.is_anonymous"))->default(0);
                        $table->integer("version")->comment(trans("vote.vote_in_version_id"));

                        $table->timestamps();
                    });
                }
            }
            if ($result->remind == 1 && $result->start_time <= date('Y-m-d H:i') && $result->active == 1) {
                $sendData['remindMark']   = 'vote-submit';
                $userId                   = $this->getVoteInUsers($voteId);
                $sendData['toUser']       = implode(',', $userId);
                $sendData['contentParam'] = ['voteTitle' => $result->vote_name, 'userName' => $userInfo['user_name']];
                $sendData['stateParams']  = ['vote_id' => $voteId];
                Eoffice::sendMessage($sendData);
            }
            return ['vote_id' => $result->id];
        }

        return ['code' => ['0x000003', 'common']];
    }
    /**
     * 编辑调查表设计器
     */
    public function editVoteDesign($data, $voteId, $userInfo)
    {
        if (!$voteId) {
            return ['code' => ['0x249001', 'vote']];
        }
        if (!isset($data['max_id'])) {
            return ['code' => ['0x249001', 'vote']];
        }
        $insertData = [];
        foreach ($data['data'] as $key => $value) {
            if(!$value) {
                continue;
            }
            if(!isset($value['control_attribute']) || empty($value['control_attribute']) || empty($value['control_id']) || empty($value['control_title']) || empty($value['control_type'])  || empty($value['vote_id']) || !isset($value['control_id']) || !isset($value['control_title']) || !isset($value['control_type']) || !isset($value['position']) || !isset($value['vote_id'])) {
                return ['code' => ['0x249001', 'vote']];
            }
            $insertData[$key]['control_attribute'] = $value['control_attribute'];
            $insertData[$key]['control_id'] = $value['control_id'];
            $insertData[$key]['control_title'] = $value['control_title'];
            $insertData[$key]['control_type'] = $value['control_type'];
            $insertData[$key]['position'] = $value['position'];
            $insertData[$key]['vote_id'] = $value['vote_id'];
        }
        if (count($insertData) > 0) {

            app($this->voteControlDesignerRepository)->deleteByWhere(['vote_id' => [$voteId]]);
            $voteInfo = $insertData;
            if (app($this->voteControlDesignerRepository)->insertMultipleData($voteInfo)) {
                $voteMInfo = app($this->voteRepository)->getDetail($voteId);
                $version   = $voteMInfo->version + 1;
                app($this->voteRepository)->updateData(['max_id' => $data['max_id'], 'version' => $version, 'vote_name' => $data['vote_name']], ['id' => [$voteId]]);

                $voteDataTableName = 'vote_data_' . $voteId;
                //生成vote_data_xx表
                if (!Schema::hasTable($voteDataTableName)) {
                    Schema::create($voteDataTableName, function ($table) {
                        $table->increments("id")->comment(trans("vote.increase_id"));
                        $table->integer("vote_id")->comment(trans("vote.vote_id"));
                        $table->string("user_id")->comment(trans("vote.vote_in_user_id"));
                        $table->tinyInteger("anonymous")->comment(trans("vote.is_anonymous"))->default(0);
                        $table->integer("version")->comment(trans("vote.vote_in_version_id"));
                        $table->timestamps();
                    });
                }
                foreach ($voteInfo as $contrilValue) {

                    if (isset($contrilValue['control_id']) && !empty($contrilValue['control_id'])) {
                        $controlKey = $contrilValue['control_id'];
                        $hasOther = (isset($contrilValue['control_attribute']['other']) && $contrilValue['control_attribute']['other']['hasOther'] == true) ? true : '';
                        $hasDescript = (isset($contrilValue['control_attribute']['descript']) && $contrilValue['control_attribute']['descript']['hasDescript'] == true) ? true : '';
                    } else {
                        continue;
                    }
                    $descriptcontrolKey = $controlKey.'_descript';
                    if($hasDescript && !Schema::hasColumn($voteDataTableName,$descriptcontrolKey )){
                        Schema::table($voteDataTableName,function ($table) use($descriptcontrolKey) {
                            $table = $table->text($descriptcontrolKey)->nullable();
                        });
                    }
                    $othercontrolKey = $controlKey.'_other';
                    if($hasOther && !Schema::hasColumn($voteDataTableName, $othercontrolKey)) {
                        Schema::table($voteDataTableName,function ($table) use($othercontrolKey) {
                            $table = $table->text($othercontrolKey)->nullable();
                        });
                    }
                    if (!Schema::hasColumn($voteDataTableName, $controlKey)) {
                        Schema::table($voteDataTableName, function ($table) use ($controlKey) {
                            $table = $table->text($controlKey)->nullable();
                        });
                    }
                }
                return true;
            }
        } else {
            app($this->voteControlDesignerRepository)->deleteByWhere(['vote_id' => [$voteId]]);
            app($this->voteRepository)->updateData(['max_id' => 0], ['id' => [$voteId]]);
            return true;
        }

    }
    /**
     * 获取参与的调查列表
     */
    public function getMineList($param, $userInfo)
    {

        //获取我参与的调查id
        $vote_ids = $this->getMineVoteId($userInfo);
        $param    = $this->parseParams($param);
        if (!empty($vote_ids)) {
            $param['vote_ids'] = $vote_ids;
        }
        $readIds = [];
        //获取已参与的投票id
        $readId = app($this->voteLogRepository)->getReadList($userInfo['user_id']);

        if($readId) {
            $readIds = $readId->pluck('vote_id')->toArray();
        }

        if(isset($param['search']['read'])) {

            if(!empty($readId)) {
                if($param['search']['read'] == 1) {
                    $param['readId'] = $readId;
                }else{
                    $param['unreadId'] = $readId;
                }
            }else{
                if($param['search']['read'] == 1) {
                    $param['readId'] = [];
                }
            }
            unset($param['search']['read']);

        }
        $list  = app($this->voteRepository)->getMineVoteList($param, $userInfo);
        foreach ($list as $key => $value) {
            if(in_array($value['id'],$readIds)) {
                $list[$key]['isVoteIn'] = 1;
            }else{
                $list[$key]['isVoteIn'] = 0;
            }
        }
        $count = app($this->voteRepository)->getMineVoteTotal($param, $userInfo);
        return ['total' => $count, 'list' => $list];

    }
    /**
     * 获取参与的开启登录发起调查的列表
     */
    public function getAfterLoginOpenList($userInfo)
    {
        if(in_array(249, $userInfo['menus']['menu'])) {
            //获取我参与的调查id
            $vote_ids = $this->getMineVoteId($userInfo);
            //获取已参与的投票id
            $readId = app($this->voteLogRepository)->getReadList($userInfo['user_id']);

            if($readId) {
                $readIds = $readId->pluck('vote_id')->toArray();
            }else{
                $readIds = [];
            }
            $param    = [
                'search' => ['after_login_open'=>[1]],
            ];
            if (!empty($vote_ids)) {
                $param['vote_ids'] = $vote_ids;
            }
            if (!empty($readIds)) {
                $param['read_vote_ids'] = $readIds;
            }
            $list = app($this->voteRepository)->getMineVoteList($param, $userInfo);

            return $list;
        }
        return [];

    }
    /**
     * 获取我参与的调查id
     */
    public function getMineVoteId($userInfo)
    {
        $userId = $userInfo['user_id'];
        $deptId = $userInfo['dept_id'];
        $roleId = $userInfo['role_id'];
        $ids    = [];
        if (!empty($userId)) {
            $userInfo = app($this->voteUserRepository)->getinfo(['search' => ['user_id' => [$userId]], 'fields' => ['vote_id']]);
            if ($userInfo) {
                foreach ($userInfo as $key => $value) {
                    $userInfo[$key] = $value['vote_id'];
                }
            } else {
                $userInfo = [];
            }
        }
        if (!empty($deptId)) {
            $deptInfo = app($this->voteDeptRepository)->getinfo(['search' => ['dept_id' => [$deptId]], 'fields' => ['vote_id']]);
            if ($deptInfo) {
                foreach ($deptInfo as $key => $value) {
                    $deptInfo[$key] = $value['vote_id'];
                }
            } else {
                $deptInfo = [];
            }
        }
        if (!empty($roleId)) {
            $roleInfo = app($this->voteRoleRepository)->getinfo(['search' => ['role_id' => [$roleId, 'in']], 'fields' => ['vote_id']]);
            if ($roleInfo) {
                foreach ($roleInfo as $key => $value) {
                    $roleInfo[$key] = $value['vote_id'];
                }
            } else {
                $roleInfo = [];
            }
        }
        $ids = array_merge($userInfo, $deptInfo, $roleInfo);
        return $ids;
    }
    /**
     * 更新调查表状态
     */
    public function updateVoteManage($data, $voteId,$userInfo)
    {
        if (!$voteId) {
            return ['code' => ['0x249001', 'vote']];
        }
        if (app($this->voteRepository)->updateData($data, ['id' => [$voteId]])) {
            if (isset($data['active']) && $data['active'] === 1) {
                //创建调查表数据分表
                $tableName = "vote_data_" . $voteId;
                if (!Schema::hasTable($tableName)) {
                    Schema::create($tableName, function ($table) {
                        $table->increments("id")->comment(trans("vote.increase_id"));
                        $table->integer("vote_id")->comment(trans("vote.vote_id"));
                        $table->string("user_id")->comment(trans("vote.vote_in_user_id"));
                        $table->tinyInteger("anonymous")->comment(trans("vote.is_anonymous"))->default(0);
                        $table->integer("version")->comment(trans("vote.vote_in_version_id"));
                        $table->timestamps();
                    });
                }
                $result = app($this->voteRepository)->getDetail($voteId);
                if ($result->remind == 1 && $result->start_time <= date('Y-m-d H:i') && $result->active == 1) {
                    $sendData['remindMark']   = 'vote-submit';
                    $userId                   = $this->getVoteInUsers($voteId);
                    $sendData['toUser']       = implode(',', $userId);
                    $sendData['contentParam'] = ['voteTitle' => $result->vote_name, 'userName' => $userInfo['user_name']];
                    $sendData['stateParams']  = ['vote_id' => $voteId];
                    Eoffice::sendMessage($sendData);
                }
            }
            return true;
        };
        return false;
    }
    /**
     * 保存调查表数据
     */
    public function saveVoteData($data, $voteId, $userInfo)
    {
        if (!$voteId) {
            return ['code' => ['0x249001', 'vote']];
        }
        if (!isset($data['data']) || empty($data['data'])) {
            return ['code' => ['0x249001', 'vote']];
        }
        if (!isset($data['version']) || empty($data['version'])) {
            return ['code' => ['0x249001', 'vote']];
        }
        if (!isset($data['vote_has_many_control']) || empty($data['vote_has_many_control'])) {
            return ['code' => ['0x249001', 'vote']];
        }
        $isAnonymous = 0;
        if(isset($data['is_anonymous']) && $data['is_anonymous'] == 1) {
            $isAnonymous = 1;
        }
        $currentDate    = date('Y-m-d H:i');
        $voteManageInfo = $this->getVoteManageInfoOutAttment($voteId);
        if (!$voteManageInfo) {
            return ['code' => ['0x249001', 'vote']];
        }

        //判断调查表是否开启,调查是否过期
        if ($voteManageInfo->active != 1 || strtotime($currentDate) > strtotime($voteManageInfo->end_time)) {
            return ['code' => ['0x249002', 'vote']];
        }
        //判断投票人是否在可投票范围内
        $vote_ids = $this->getMineVoteId($userInfo);
        if (!in_array($voteId, $vote_ids) && $voteManageInfo->all_user != 1) {
            return ['code' => ['0x249003', 'vote']];
        }
        //判断是否可以重复提交
        if ($voteManageInfo->multiple_submit != 1) {
            $tableName = "vote_data_" . $voteId;
            if (Schema::hasTable($tableName)) {
                $handelCount = DB::table($tableName)->where(['user_id' => $userInfo['user_id']])->count();
                if ($handelCount > 0) {
                    return ['code' => ['0x249004', 'vote']];
                }
            }

        }
        //表单控件信息列表
        $controlInfo = $voteManageInfo->voteHasManyControl->toArray();

        //原版本信息
        $oldVersion = $voteManageInfo->version;
        //判断版本是否变更
        if ($data['version'] != $oldVersion) {
            $controlInfo = $data['vote_has_many_control'];
            $versionInfo = [
                'vote_id'      => $voteId,
                'version'      => $data['version'],
                'version_info' => json_encode($data['vote_has_many_control']),
            ];
            app($this->voteVersionRepository)->insertData($versionInfo);
        }

        //根据调查表字段设置 验证字段值是否符合设置要求
        $error = [];
        foreach ($controlInfo as $control) {
            if (!isset($control['control_type']) || !isset($control['control_id']) || !isset($control['control_attribute'])) {
                return ['code' => ['0x249005', 'vote']];
            } else {
                $controlData = '';

                $controlId = $control['control_id'];
                if (isset($data['data'][$controlId])) {
                    $controlData = $data['data'][$controlId];
                }
                //检测数据是否符合控件设置要求
                $checkResult = $this->checkVoteControlValue($control, $voteId, $controlData);
                $error       = array_merge($error, $checkResult);
            }
        }
        $new_data = [];
        //处理多选、其他、说明
        foreach ($data['data'] as $control_title => $control_data) {
            if(is_array($control_data)) {
                foreach ($control_data as $index_item => $value_item) {
                    if(strpos($value_item, 'id_') !== false) {
                        $control_data[$index_item] = explode('_',$value_item)[1];
                    }
                }
            }else{
                if(strpos($control_data, 'id_') !== false) {
                    $control_data = explode('_',$control_data)[1];
                }
            }
            if($control_title == 'other') {
                foreach ($control_data as $k => $v) {
                    if($v) {
                        $_tableName = "vote_data_" . $voteId;
                        $_key = $k.'_other';
                        if (!Schema::hasColumn($_tableName, $_key)) {
                            Schema::table($_tableName, function ($table) use ($_key) {
                                $table = $table->text($_key)->nullable();
                            });
                        }
                        $new_data[$_key]  = $v;
                    }
                }
            }else if($control_title == 'descript') {
                foreach ($control_data as $k => $v) {
                    if($v) {
                        $_tableName = "vote_data_" . $voteId;
                        $_key = $k.'_descript';
                        if (!Schema::hasColumn($_tableName, $_key)) {
                            Schema::table($_tableName, function ($table) use ($_key) {
                                $table = $table->text($_key)->nullable();
                            });
                        }
                        $new_data[$_key]  = $v;
                    }
                }
            }else{
                if (is_array($control_data)) {
                    $new_data[$control_title] = implode(',', $control_data);
                }else {
                    $new_data[$control_title] = $control_data;
                }
            }

        }
        if (empty($error)) {
            $voteFormData               = $new_data;
            $voteFormData['vote_id']    = $voteId;
            $voteFormData['user_id']    = $userInfo['user_id'];
            $voteFormData['version']    = $data['version'];
            $voteFormData['anonymous']    = $isAnonymous;
            $voteFormData['created_at'] = date("Y-m-d H:i:s");
            $voteFormData['updated_at'] = date("Y-m-d H:i:s");
            $tableName                  = "vote_data_" . $voteId;
            if($id = DB::table($tableName)->insertGetId($voteFormData)) {
                //添加投票日志
                $logData = [
                    'vote_id' => $voteId,
                    'vote_data_id' => $id,
                    'user_id' => $userInfo['user_id'],
                ];
                app($this->voteLogRepository)->insertData($logData);
                if(DB::table('vote_manage')->where('id',$voteId)->update(['submit' => $voteManageInfo->submit + 1])) {
                    return true;
                }
            }
        }
        return $error;

    }
    /**
     * 验证调查表字段值是否符合设置要求
     * $data:控件信息
     * $voteId:调查表id
     * $value:控件值
     */
    public function checkVoteControlValue($data, $voteId, $value)
    {
        $error            = [];
        $title            = isset($data['control_title']) ? $data['control_title'] : '';
        $type             = $data['control_type'];
        $controlId        = $data['control_id'];
        $controlAttribute = json_decode($data['control_attribute'], true);
        if(isset($controlAttribute['itemHidden']) && ($controlAttribute['itemHidden'] == true || $controlAttribute['itemHidden'] == 'true')) {
            return [];
        }
        // //获取自定义出错文案
        // $errorTips = '';
        // if(isset($controlAttribute['controlAttribute'])&& !empty($controlAttribute['controlAttribute'])){
        //     $errorTips = $controlAttribute['controlAttribute'];
        // };
        //验证必填设置
        $isRequired = false;
        if (isset($controlAttribute['required']) && $controlAttribute['required'] == true) {
            $isRequired = true;
        }
        //验证必填

        if ($isRequired && (!$value && $value != 0)) {
            $error[] = $title ? $title . trans("vote.required") : $controlId . trans("vote.required");
        }
        if($value) {
            //验证控件
            switch ($type) {
                case 'text': //单行文本控件
                    //必填
                    //不能和已有数据重复
                    //最多填多少个字
                    //最少填多少个字

                    //获取不能和已有数据重复设置
                    $isUnique = false;
                    if (isset($controlAttribute['isUnique']) && $controlAttribute['isUnique'] == true) {
                        $isUnique = true;
                    }
                    if ($isUnique) {
                        //获取已有数据
                        $existingData = $this->getExistingData($voteId, $controlId);
                        if (!empty($existingData) && in_array($value, $existingData)) {
                            $error[] = $title ? $title . trans("vote.unique") : $controlId . trans("vote.unique");
                        }
                    }
                    //获取最多填多少字设置
                    $maxText = 0;
                    if (isset($controlAttribute['maxText']) && isset($controlAttribute['maxText']['isLimit']) && $controlAttribute['maxText']['isLimit'] == true) {
                        $maxText = isset($controlAttribute['maxText']['number']) ? $controlAttribute['maxText']['number'] : 0;
                    }
                    if ($maxText && mb_strlen($value) > $maxText) {
                        $error[] = $title ? $title . trans("vote.length_too_long") : $controlId . trans("vote.length_too_long");
                    }
                    //获取最少填多少字设置
                    $minText = 0;
                    if (isset($controlAttribute['minText']) && isset($controlAttribute['minText']['isLimit']) && $controlAttribute['minText']['isLimit'] == true) {
                        $minText = isset($controlAttribute['minText']['number']) ? $controlAttribute['minText']['number'] : 0;
                    }
                    if ($minText && mb_strlen($value) < $minText) {
                        $error[] = $title ? $title . trans("vote.length_too_short") : $controlId . trans("vote.length_too_short");
                    }

                    break;
                case 'number': //数字
                    //必填
                    //最大值
                    //最小值

                    //最大值
                    $maxText = 0;
                    if (isset($controlAttribute['maxText']) && isset($controlAttribute['maxText']['isLimit']) && $controlAttribute['maxText']['isLimit'] == true) {
                        $maxText = isset($controlAttribute['maxText']['number']) ? $controlAttribute['maxText']['number'] : 0;
                    }
                    if ($maxText && $value > $maxText) {
                        $error[] = $title ? $title . trans("vote.number_too_big") : $controlId . trans("vote.number_too_big");
                    }
                    //最小值
                    $minText = 0;
                    if (isset($controlAttribute['minText']) && isset($controlAttribute['minText']['isLimit']) && $controlAttribute['minText']['isLimit'] == true) {
                        $minText = isset($controlAttribute['minText']['number']) ? $controlAttribute['minText']['number'] : 0;
                    }
                    if ($minText && $value < $minText) {
                        $error[] = $title ? $title . trans("vote.number_too_small") : $controlId . trans("vote.number_too_small");
                    }

                    break;
                case 'textarea': //多行文本控件
                    //必填
                    //最多填多少个字
                    //最少填多少个字

                    //获取最多填多少字设置
                    $maxText = 0;
                    if (isset($controlAttribute['maxText']) && isset($controlAttribute['maxText']['isLimit']) && $controlAttribute['maxText']['isLimit'] == true) {
                        $maxText = isset($controlAttribute['maxText']['number']) ? $controlAttribute['maxText']['number'] : 0;
                    }
                    if ($maxText && mb_strlen($value) > $maxText) {
                        $error[] = $title ? $title . trans("vote.length_too_long") : $controlId . trans("vote.length_too_long");
                    }
                    //获取最少填多少字设置
                    $minText = 0;
                    if (isset($controlAttribute['minText']) && isset($controlAttribute['minText']['isLimit']) && $controlAttribute['minText']['isLimit'] == true) {
                        $minText = isset($controlAttribute['minText']['number']) ? $controlAttribute['minText']['number'] : 0;
                    }
                    if ($minText && mb_strlen($value) < $minText) {
                        $error[] = $title ? $title . trans("vote.length_too_short") : $controlId . trans("vote.length_too_short");
                    }

                    break;
                case 'timePicker': //时间控件
                    //必填

                    break;
                case 'datePicker': //日期控件
                    //必填
                    //起始日期
                    //截止日期

                    //获取起始日期设置
                    $startDate = '';
                    if (isset($controlAttribute['minText']) && isset($controlAttribute['minText']['isLimit']) && $controlAttribute['minText']['isLimit'] == true) {
                        $startDate = isset($controlAttribute['minText']['number']) ? $controlAttribute['minText']['number'] : '';
                    }
                    if ($startDate && $value < $startDate) {
                        $error[] = $title ? $title . trans("vote.date_too_soon"): $controlId .  trans("vote.date_too_soon");
                    }
                    //获取截止日期设置
                    $endDate = '';
                    if (isset($controlAttribute['minText']) && isset($controlAttribute['minText']['isLimit']) && $controlAttribute['minText']['isLimit'] == true) {
                        $endDate = isset($controlAttribute['minText']['number']) ? $controlAttribute['minText']['number'] : '';
                    }
                    if ($endDate && $value < $endDate) {
                        $error[] = $title ? $title . trans("vote.date_too_later") : $controlId . trans("vote.date_too_later");
                    }

                    break;
                case 'radio': //单选控件
                    if(strpos($value, 'id_') !== false) {
                        $value = explode('_',$value)[1];
                    }
                    //必填
                    //每一个选项的名额限制
                    //获取每个选项的已选择数量
                    $count = $this->getCheckedCount($voteId, $controlId);
                    if (!empty($count)) {
                        foreach ($controlAttribute['value'] as $v) {
                            if ($value != $v['id'] || !isset($count[$value])) {
                                continue;
                            }
                            //获取限制名额
                            $limit = '';
                            if (isset($v['quotaLimit']) && isset($v['quotaLimit']['isLimit']) && $v['quotaLimit']['isLimit'] == true) {
                                $limit = isset($v['quotaLimit']['number']) ? $v['quotaLimit']['number'] : '';
                            }
                            if (isset($count[$value]) && $limit && $limit <= $count[$value]) {
                                $error[] = $title . $v['name'] . trans("vote.quota_is_full");
                            }

                        }
                    }
                    break;
                case 'checkbox': //多选控件
                    //必填
                    //每一个选项的名额限制
                    //获取每个选项的已选择数量
                    $count = $this->getCheckedCount($voteId, $controlId);
                    if (!empty($count)) {
                        foreach ($controlAttribute['value'] as $v) {
                            foreach ($value as $control_title_checkbox => $status) {
                                if(strpos($status, 'id_') !== false) {
                                    $status = explode('_',$status)[1];
                                }
                                if ($status == $v['id']  && isset($count[$status])) {
                                    //获取限制名额
                                    $limit = '';
                                    if (isset($v['quotaLimit']) && isset($v['quotaLimit']['isLimit']) && $v['quotaLimit']['isLimit'] == true) {
                                        $limit = isset($v['quotaLimit']['number']) ? $v['quotaLimit']['number'] : '';
                                    }
                                    if ($limit && $limit <= $count[$status]) {
                                        $error[] = $title . $v['name'] . trans("vote.quota_is_full");
                                    }
                                }
                            }
                        }
                    }
                    break;
                case 'imageRadio': //图片单选控件
                    if(strpos($value, 'id_') !== false) {
                        $value = explode('_',$value)[1];
                    }
                    //必填
                    //每一个选项的名额限制
                    $count = $this->getCheckedCount($voteId, $controlId);

                    if (!empty($count)) {
                        foreach ($controlAttribute['value'] as $v) {
                            //匹配设置项与验证项
                            if ($value != $v['id'] || !isset($count[$value])) {
                                continue;
                            }
                            //获取限制名额
                            $limit = '';
                            if (isset($v['quotaLimit']) && isset($v['quotaLimit']['isLimit']) && $v['quotaLimit']['isLimit'] == true) {
                                $limit = isset($v['quotaLimit']['number']) ? $v['quotaLimit']['number'] : '';
                            }

                            if ($limit && $limit <= $count[$value]) {
                                $error[] = $title . $v['name'] . trans("vote.quota_is_full");
                            }

                        }
                    }
                    break;
                case 'imageCheckbox': //图片多选控件
                    //必填
                    //每一个选项的名额限制
                    $count = $this->getCheckedCount($voteId, $controlId);
                    if (!empty($count)) {
                        foreach ($controlAttribute['value'] as $v) {
                            foreach ($value as $control_title_checkbox => $status) {
                                if(strpos($status, 'id_') !== false) {
                                    $status = explode('_',$status)[1];
                                }
                                if ($status == $v['id']  && isset($count[$status])) {
                                    //获取限制名额
                                    $limit = '';
                                    if (isset($v['quotaLimit']) && isset($v['quotaLimit']['isLimit']) && $v['quotaLimit']['isLimit'] == true) {
                                        $limit = isset($v['quotaLimit']['number']) ? $v['quotaLimit']['number'] : '';
                                    }
                                    if ($limit && $limit <= $count[$status]) {
                                        $error[] = $title . $v['name'] . trans("vote.quota_is_full");
                                    }
                                }
                            }
                        }
                    }
                    //最少选多少项
                    //最多选多少项

                    break;
                case 'select': //下拉控件
                    //必填
                    //每一个选项的名额限制
                    $count = $this->getCheckedCount($voteId, $controlId);
                    if (!empty($count)) {
                        foreach ($controlAttribute['value'] as $v) {
                            //匹配设置项与验证项
                            if ($value != $v['id'] || !isset($count[$value])) {
                                continue;
                            }
                            //获取限制名额
                            $limit = '';
                            if (isset($v['quotaLimit']) && isset($v['quotaLimit']['isLimit']) && $v['quotaLimit']['isLimit'] == true) {
                                $limit = isset($v['quotaLimit']['number']) ? $v['quotaLimit']['number'] : '';
                            }
                            if ($limit && $limit <= $count[$value]) {
                                $error[] = $title . $v['name'] . trans("vote.quota_is_full");
                            }

                        }
                    }

                    break;
                case 'twiceSelect': //两级下拉控件
                    //必填

                    break;
                    //case '附件':
                    //必填
                    //最大文件数量
                    //文件上传类型

            };
        }

        if (!empty($error)) {
            return $error;
        }
        return [];

    }
    /**
     * 获取调查表某个字段已有的数据
     */
    public function getExistingData($voteId, $controlId)
    {
        $tableName = "vote_data_" . $voteId;
        $result    = [];
        if (Schema::hasTable($tableName)) {
            if (Schema::hasColumn($tableName, $controlId)) {
                $result = DB::table($tableName)->select([$controlId, 'user_id', 'id'])->where(['vote_id' => $voteId])->get();
                // 过滤只取最后一次投票进行统计
                $result = $result->sortByDesc('id')->unique('user_id');
                $result = $result->pluck($controlId);
                if ($result) {
                    $result = $result->toArray();
                    $result = array_filter($result);
                }
            }
        }
        return $result;
    }
    /**
     * 获取单选多选已选择的数据统计
     */
    public function getCheckedCount($voteId, $controlId)
    {
        $result = $this->getExistingData($voteId, $controlId);
        $count   = [];
        $checked = '';
        foreach ($result as $key => $value) {
            $checked .= $checked ? (',' . $value) : ('' . $value);
        }
        $newResult = explode(',', $checked);
        return array_count_values(array_filter($newResult));
    }
    /**
     * 获取参与统计人员
     */
    public function getVoteInUser($voteId, $userInfo)
    {
        if (!$voteId) {
            return ['code' => ['0x249001', 'vote']];
        }

        $voteInfo = $this->getVoteManageInfoOutAttment($voteId);
        $result   = [
            'dept_info' => [],
            'total' => 0,
            'vote_count' => 0,
            'anonymous_vote_count' => 0
        ];
        if ($voteInfo) {
            $voteInfo = $voteInfo->toArray();
            if (isset($voteInfo['vote_has_many_user']) && !empty($voteInfo['vote_has_many_user'])) {
                foreach ($voteInfo['vote_has_many_user'] as $key => $value) {
                    $voteInfo['vote_has_many_user'][$key] = $value['user_id'];
                }
            }
            if (isset($voteInfo['vote_has_many_dept']) && !empty($voteInfo['vote_has_many_dept'])) {
                foreach ($voteInfo['vote_has_many_dept'] as $key => $value) {
                    $voteInfo['vote_has_many_dept'][$key] = $value['dept_id'];
                }
            }
            if (isset($voteInfo['vote_has_many_role']) && !empty($voteInfo['vote_has_many_role'])) {
                foreach ($voteInfo['vote_has_many_role'] as $key => $value) {
                    $voteInfo['vote_has_many_role'][$key] = $value['role_id'];
                }
            }
            $result = app($this->voteRepository)->getVoteUsers($voteId, $voteInfo);

        }
        $list = $result['dept_info'];
//        if($result && is_array($result)){
//            foreach ($result as $key => $vo){
//                isset($vo['vote_in']) && $vo['vote_in'] && sort($result[$key]['vote_in']);
//                $result[$key]['diff_count'] = 0;
//                if(isset($vo['count'])){
//                    $vo['count'] = array_unique($vo['count']);
//                    $result[$key]['diff_count'] = count($vo['count']);
//                    unset($result[$key]['count']);
//                }
//            }
//            sort($result);
//        }

        return [
            'list' => $list,
            'vote_total' => $result['total'],
            'anonymous_vote_count' => $result['anonymous_vote_count'],
            'vote_count' => $result['vote_count'],
            'vote_manage' => $voteInfo
        ];

    }
    /**
     * 获取发布范围人员
     */
    public function getVoteInUsers($voteId)
    {
        if (!$voteId) {
            return ['code' => ['0x249001', 'vote']];
        }
        $voteInfo = $this->getVoteManageInfoOutAttment($voteId);
        $result   = [];
        if ($voteInfo) {
            $voteInfo = $voteInfo->toArray();
            if (isset($voteInfo['vote_has_many_user']) && !empty($voteInfo['vote_has_many_user'])) {
                foreach ($voteInfo['vote_has_many_user'] as $key => $value) {
                    $voteInfo['vote_has_many_user'][$key] = $value['user_id'];
                }
            }
            if (isset($voteInfo['vote_has_many_dept']) && !empty($voteInfo['vote_has_many_dept'])) {
                foreach ($voteInfo['vote_has_many_dept'] as $key => $value) {
                    $voteInfo['vote_has_many_dept'][$key] = $value['dept_id'];
                }
            }
            if (isset($voteInfo['vote_has_many_role']) && !empty($voteInfo['vote_has_many_role'])) {
                foreach ($voteInfo['vote_has_many_role'] as $key => $value) {
                    $voteInfo['vote_has_many_role'][$key] = $value['role_id'];
                }
            }
            $result = app($this->voteRepository)->getVoteInUsers($voteId, $voteInfo);
        }
        return $result;
    }
    /**
     * 获取投票明细
     */
    public function getVoteResult($voteId)
    {
        if (!$voteId) {
            return ['code' => ['0x249001', 'vote']];
        }

        $voteManageInfo = $this->getVoteManageInfoOutAttment($voteId);
        if (!$voteManageInfo) {
            return ['code' => ['0x249001', 'vote']];
        }

        $controlInfo = json_decode($voteManageInfo->voteHasManyControl, true);
        $result      = [];
        $i           = 0;
        foreach ($controlInfo as $key => $value) {
            $title            = isset($value['control_title']) ? $value['control_title'] : '';
            $type             = $value['control_type'];
            $controlId        = $value['control_id'];
            $controlAttribute = json_decode($value['control_attribute'], true);
            if(isset($controlAttribute['itemHidden']) && $controlAttribute['itemHidden'] == true) {
                continue;
            }
            //验证控件
            switch ($type) {

                case 'number': //数字
                    $data                     = $this->getExistingData($voteId, $controlId);
                    $result[$i]['title']      = $title;
                    $result[$i]['type']       = $type;
                    $result[$i]['control_id'] = $controlId;
                    $result[$i]['ave']        = count($data)>0 ? array_sum($data) / count($data) : 0;
                    $result[$i]['max']        = count($data)>0 ? max($data) : 0;
                    $result[$i]['min']        = count($data)>0 ? min($data) : 0;
                    $result[$i]['sum']        = count($data)>0 ? array_sum($data) : 0;
                    $i++;
                    break;

                case 'radio': //单选控件
                    $optionInfo               = $this->getOptionInfo($controlAttribute, $voteId, $controlId);
                    $result[$i]['option']     = $optionInfo;
                    $result[$i]['title']      = $title;
                    $result[$i]['type']       = $type;
                    $result[$i]['control_id'] = $controlId;
                    $i++;
                    break;
                case 'checkbox': //多选控件
                    $optionInfo               = $this->getOptionInfo($controlAttribute, $voteId, $controlId);
                    $result[$i]['option']     = $optionInfo;
                    $result[$i]['title']      = $title;
                    $result[$i]['type']       = $type;
                    $result[$i]['control_id'] = $controlId;
                    $i++;
                    break;
                case 'imageRadio': //图片单选控件
                    $optionInfo               = $this->getOptionInfo($controlAttribute, $voteId, $controlId);
                    $result[$i]['option']     = $optionInfo;
                    $result[$i]['title']      = $title;
                    $result[$i]['type']       = $type;
                    $result[$i]['control_id'] = $controlId;
                    $i++;
                    break;
                case 'imageCheckbox': //图片多选控件
                    $optionInfo               = $this->getOptionInfo($controlAttribute, $voteId, $controlId);
                    $result[$i]['option']     = $optionInfo;
                    $result[$i]['title']      = $title;
                    $result[$i]['type']       = $type;
                    $result[$i]['control_id'] = $controlId;
                    $i++;
                    break;
                case 'select': //下拉控件
                    $optionInfo               = $this->getOptionInfo($controlAttribute, $voteId, $controlId);
                    $result[$i]['option']     = $optionInfo;
                    $result[$i]['title']      = $title;
                    $result[$i]['type']       = $type;
                    $result[$i]['control_id'] = $controlId;
                    $i++;
                    break;
            };
        }
        if(empty($result)){
            return ['code' => ['0x249008', 'vote']];
        }
        return $result;

    }
    /**
     * 解析单选多选选项
     */
    public function getOptionInfo($controlAttribute, $voteId, $controlId)
    {
        $optionInfo   = [];
        $selcetedData = $this->getCheckedCount($voteId, $controlId);
        if (isset($controlAttribute['value']) && !empty($controlAttribute['value'])) {
            foreach ($controlAttribute['value'] as $key => $value) {
                $optionInfo[] = [
                    'name'  => $value['name'],
                    'id'    => $value['id'],
                    'count' => isset($selcetedData[$value['id']]) ? $selcetedData[$value['id']] : 0,
                    'total' => array_sum($selcetedData),
                ];
            }
        }
        return $optionInfo;
    }
    /**
     * 调查表数据列表
     */
    public function getVoteDataList($voteId, $data, $userInfo)
    {
        $data = $this->parseParams($data);
        if (!$voteId) {
            return ['code' => ['0x249001', 'vote']];
        }
        if(isset($data['search']) && $data['search']){
            $search = $data['search'];
            if($search && (isset($data['anonymous']) && $data['anonymous'])){
                return ['code' => ['anonymous_error_search', 'vote']];
            }
            $data['user_name'] = $search['user_name'][0];
        }
        $tableName = "vote_data_" . $voteId;
        $voteIn    = [];
        $count     = 0;
        if (Schema::hasTable($tableName)) {
            $count  = app($this->voteRepository)->getVoteDataCount($tableName, $data, $voteId);
            $result = app($this->voteRepository)->getVoteDataList($tableName, $data, $voteId);
            if ($result) {
                $voteIn = $result->toArray();
            }
        }
        return ['total' => $count, 'list' => $voteIn];
    }
    /**
     * 调查表数据列表
     */
    public function getVoteInDetail($Id, $data, $userInfo)
    {
        if (!$Id) {
            return ['code' => ['0x249001', 'vote']];
        }
        if (!isset($data['vote_id']) || empty($data['vote_id'])) {
            return ['code' => ['0x249001', 'vote']];
        }
        $voteId    = $data['vote_id'];
        $tableName = "vote_data_" . $voteId;

        $voteManageInfo = $this->getVoteManageInfoOutAttment($voteId);
        if (!$voteManageInfo) {
            return ['code' => ['0x249001', 'vote']];
        }
        $result    = [];
        $newResult = [];
        if (Schema::hasTable($tableName)) {
            $result         = app($this->voteRepository)->getVoteDataDetail($tableName, $Id);
            $voteManageControlInfo = $voteManageInfo->voteHasManyControl;
            foreach ($voteManageControlInfo as $key => $value) {
                $type                   = $value['control_type'];
                $control_id             = $value['control_id'];
                $newResult[$control_id] = $result->$control_id;
                $_other = $control_id.'_other';
                $_explain = $control_id.'_descript';
                //验证控件
                switch ($type) {
                    case 'number': //数字
                        $newResult[$control_id] = $newResult[$control_id];
                        break;

                    case 'radio': //单选控件
                        $newResult[$control_id] = intval($newResult[$control_id]);

                        if(isset($result->$_other)) {
                            $newResult['other'][$control_id] = $result->$_other;
                        }
                        if(isset($result->$_explain)) {
                            $newResult['descript'][$control_id] = $result->$_explain;
                        }
                        break;
                    case 'checkbox': //多选控件
                        $newResult[$control_id] = explode(',', $newResult[$control_id]);
                        foreach ($newResult[$control_id] as $k => $v) {
                            $newResult[$control_id][$k] = intval($v);

                        }
                        if(isset($result->$_other)) {
                            $newResult['other'][$control_id] = $result->$_other;
                        }
                        if(isset($result->$_explain)) {
                            $newResult['descript'][$control_id] = $result->$_explain;
                        }
                        break;
                    case 'imageRadio': //图片单选控件
                        $newResult[$control_id] = intval($newResult[$control_id]);
                        if(isset($result->$_other)) {
                            $newResult['other'][$control_id] = $result->$_other;
                        }
                        if(isset($result->$_explain)) {
                            $newResult['descript'][$control_id] = $result->$_explain;
                        }
                        break;
                    case 'imageCheckbox': //图片多选控件
                        $newResult[$control_id] = explode(',', $newResult[$control_id]);
                        foreach ($newResult[$control_id] as $k => $v) {
                            $newResult[$control_id][$k] = intval($v);
                        }
                        if(isset($result->$_other)) {
                            $newResult['other'][$control_id] = $result->$_other;
                        }
                        if(isset($result->$_explain)) {
                            $newResult['descript'][$control_id] = $result->$_explain;
                        }
                        break;
                    case 'select': //下拉控件
                        $newResult[$control_id] = intval($newResult[$control_id]);
                        if(isset($result->$_explain)) {
                            $newResult['descript'][$control_id] = $result->$_explain;
                        }
                        if(isset($result->$_other)) {
                            $newResult['other'][$control_id] = $result->$_other;
                        }
                        break;
                    case 'twiceSelect': //下拉控件
                        $newResult[$control_id] = explode(',', $newResult[$control_id]);
                        foreach ($newResult[$control_id] as $k => $v) {
                            $newResult[$control_id][$k] = intval($v);
                        }
                        if(isset($result->$_explain)) {
                            $newResult['descript'][$control_id] = $result->$_explain;
                        }
                        break;
                    case 'attachment':
                        $control_id = $value['control_id'];
                        if(isset($result->{$control_id}) && $result->{$control_id}){
                            $newResult[$control_id] = explode(',',$result->{$control_id});
                        }
                        break;
                };
            }

        }
        return ['data' => $newResult, 'vote_info' => $voteManageControlInfo, 'vote_manage' => $voteManageInfo];

    }
    /**
     * 数据导出
     */
    public function exportVoteData($params)
    {
        $header = [];
        $data   = [];
        if (!isset($params['vote_id']) || empty($params['vote_id'])) {
            return ['code' => ['0x249001', 'vote']];
        }
        $voteId    = $params['vote_id'];
        $tableName = "vote_data_" . $voteId;
        $voteIn    = [];

        if(isset($params['isanonymous']) && $params['isanonymous']) {
            $data['isanonymous'] = true;
        }
        unset($params['isanonymous']);
        if(isset($params['search']['user_name']) && $params['search']['user_name']) {
            $data['user_name'] = $params['search']['user_name'];
        }
        unset($params['search']['user_name']);
        if (Schema::hasTable($tableName)) {
            $result = app($this->voteRepository)->getVoteDataListForExport($tableName, $data, $voteId);
            if ($result) {
                $voteIn = $result->toArray();
            }
        }
        $voteInfo = app($this->voteControlDesignerRepository)->getVoteControlDesigner($voteId);
        $vote     = [];
        if ($voteInfo) {
            $voteInfo = $voteInfo->toArray();

            $header['user_name'] = trans("vote.submitter");
            $header['dept_name'] = trans("vote.department");
            $header['time']      = trans("vote.vote_time");
            foreach ($voteInfo as $key => $value) {
                // 过滤描述的数据
                if ($value['control_type'] === 'editor') {
                    continue;
                }
                $controlAttribute = json_decode($value['control_attribute'], true);
                if(isset($controlAttribute['itemHidden']) && $controlAttribute['itemHidden'] == true) {
                    continue;
                }
                $header[$value['control_id']] = $value['control_title'];
                $vote[$value['control_id']]   = $value;
            }
            foreach ($voteIn as $k => $v) {
                $username = $v->user_name;
                $deptname = $v->dept_name;
                $time     = $v->created_at;
                if($v->anonymous == 1 ) {
                    $username = trans("vote.anonymous");
                }
                $data[$k]['user_name'] = $username;
                $data[$k]['dept_name'] = $deptname;
                $data[$k]['time']      = $time;
                unset($v->anonymous);
                unset($v->user_name);
                unset($v->dept_name);
                unset($v->created_at);
                unset($v->id);
                unset($v->vote_id);
                unset($v->user_id);
                unset($v->version);
                unset($v->created_at);
                unset($v->updated_at);
                foreach ($v as $control => $item) {
                    if (isset($vote[$control])) {
                        $type               = $vote[$control]['control_type'];
                        $data[$k][$control] = $item;
                        $controlInfo        = json_decode($vote[$control]['control_attribute'], true);

                        $newcontrolInfo = [];
                        if (isset($controlInfo['value'])) {
                            foreach ($controlInfo['value'] as $m) {

                                if(isset($m['value']) && !empty($m['value'])) {
                                    $newcontrolInfo[$m['id']]['name'] = $m['name'];
                                    foreach ($m['value'] as $n) {
                                        $newcontrolInfo[$m['id']][$n['id']] = $n['name'];
                                    }
                                }else{
                                    $newcontrolInfo[$m['id']] = $m['name'];
                                }
                            }
                        }
                        $_descript = '';
                        switch ($type) {

                            case 'radio': //单选控件
                                $data[$k][$control] = '';
                                $data[$k][$control] = isset($newcontrolInfo[$item]) ? $newcontrolInfo[$item] : '';
                                break;
                            case 'checkbox': //多选控件
                                $items              = explode(',', $item);
                                $data[$k][$control] = '';
                                foreach ($items as $i) {
                                    if(!empty($data[$k][$control])) {
                                        $data[$k][$control] .= ' ,' . (isset($newcontrolInfo[$i]) ? $newcontrolInfo[$i] : '');
                                    }else{
                                        $data[$k][$control] = isset($newcontrolInfo[$i]) ? $newcontrolInfo[$i] : '';
                                    }
                                }
                                break;
                            case 'imageRadio': //图片单选控件
                                $data[$k][$control] = '';
                                $data[$k][$control] = isset($newcontrolInfo[$item]) ? $newcontrolInfo[$item] : '';

                                break;
                            case 'imageCheckbox': //图片多选控件
                                $items              = explode(',', $item);
                                $data[$k][$control] = '';
                                foreach ($items as $i) {
                                    if(!empty($data[$k][$control])) {
                                        $data[$k][$control] .= ' ,' . (isset($newcontrolInfo[$i]) ? $newcontrolInfo[$i] : '');
                                    }else{
                                        $data[$k][$control] = isset($newcontrolInfo[$i]) ? $newcontrolInfo[$i] : '';
                                    }
                                }
                                break;
                            case 'select': //下拉控件
                                $data[$k][$control] = '';
                                $data[$k][$control] = isset($newcontrolInfo[$item]) ? $newcontrolInfo[$item] : '';

                                break;
                            case 'twiceSelect': //下拉控件
                                $items              = explode(',', $item);
                                $dataTemp = '';
                                if(isset($items[0]) && !empty($items[0])) {
                                    $valTemp = Arr::get($newcontrolInfo, $items[0] . '.name', '');
                                    $dataTemp = $valTemp;
                                }
                                if(isset($items[1]) && !empty($items[1])) {
                                    $valTemp = Arr::get($newcontrolInfo, $item[0] . '.' . $items[1]);
                                    $valTemp && $dataTemp .= ' -> ' . $valTemp;
                                }
                                $data[$k][$control] = $dataTemp;
                                break;
                            case 'attachment': //附件
                                $attachmentIds = $data[$k][$control];
                                if ($attachmentIds) {
                                    $attachmentIds = explode(',', $attachmentIds);
                                    $attachments = app($this->attachmentService)->getMoreAttachmentById($attachmentIds, false);
                                    if ($attachments) {
                                        $attachmentNames = Arr::pluck($attachments, 'attachment_name');
                                        $data[$k][$control] = implode(', ',$attachmentNames);
                                        unset($attachments);
                                    }
                                }
                                break;
                        };
                        //其他选项
                        $_other = $control.'_other';
                        if(isset($v->$_other) && !empty($v->$_other)) {
                            $data[$k][$control] .= trans("vote.vote_adddec") .$v->$_other;
                        }
                        //说明属性
                        $_descript = $control.'_descript';
                        if(isset($v->$_descript) && !empty($v->$_descript)) {
                            $data[$k][$control] .= trans("vote.vote_description") .$v->$_descript;
                        }

                    }
                }
            }
            return compact('header', 'data');
        } else {
            return ['code' => ['0x249001', 'vote']];
        }

    }

    /**
     * 为参数赋予默认值
     *
     * @param type $key 键值
     * @param array $data 原来的数据
     * @param type $default 默认值
     *
     * @return type 处理后的值
     *
     * @author 李志军
     *
     * @since 2015-10-17
     */
    private function defaultValue($key, $data, $default)
    {
        return isset($data[$key]) ? $data[$key] : $default;
    }
    /**
     * 获取样式列表
     */
    public function getVoteModeList($params)
    {
        $param = $this->parseParams($params);
        $list  = app($this->voteModeRepository)->getModeList($param);
        $count = app($this->voteModeRepository)->getModeTotal($param);
        //获取已占用的样式id
        $voteModeUsed = [];
        $voteModeUsed = app($this->voteRepository)->getVoteModeUsedList();
        if($voteModeUsed) {
            $voteModeUsed = $voteModeUsed->pluck('template')->toArray();
        }
        foreach ($list as $key => $value) {
            if($value['mode_type'] == 1) {
                $list[$key]['mode_title'] = trans('vote.default_mode_title');
            }
            if(in_array($value['mode_id'],$voteModeUsed)) {
                $list[$key]['isUsed'] = 1;
            }else{
                $list[$key]['isUsed'] = 0;
            }
        }
        return ['total' => $count, 'list' => $list];
    }
    /**
     * 新建样式
     */
    public function addVoteMode($data)
    {
        if(!isset($data['mode_title']) || empty($data['mode_title'])) {
            return ['code' => ['0x249001', 'vote']];
        }
        if(!isset($data['mode_content']) || empty($data['mode_content'])) {
            $data['mode_content'] = '';
        }
        if (app($this->voteModeRepository)->insertData(['mode_content' => $data['mode_content'], 'mode_title' => $data['mode_title']])) {
            return true;
        }
        return ['code' => ['0x249001', 'vote']];
    }
    /**
     * 编辑样式
     */
    public function editVoteMode($modeId, $data)
    {
        if ($modeId == 0) {
            return ['code' => ['0x249001', 'vote']];
        }
        if(!isset($data['mode_title']) || empty($data['mode_title'])) {
            return ['code' => ['0x249001', 'vote']];
        }
        if(!isset($data['mode_content']) || empty($data['mode_content'])) {
            $data['mode_content'] = '';
        }
        if (app($this->voteModeRepository)->updateData(['mode_content' => $data['mode_content'], 'mode_title' => $data['mode_title']], ['mode_id' => [$modeId]])) {
            return true;
        }
        return true;
    }
    /**
     * 删除样式
     */
    public function deleteVoteMode($modeId)
    {
        if ($modeId == 0) {
            return ['code' => ['0x249001', 'vote']];
        }
        //获取已占用的样式id
        $voteModeUsed = [];
        $voteModeUsed = app($this->voteRepository)->getVoteModeUsedList();
        if($voteModeUsed) {
            $voteModeUsed = $voteModeUsed->pluck('template')->toArray();
        }
        $error = 0;
        $modeIdArray = explode(',', $modeId);
        foreach ($modeIdArray as  $value) {
            if(in_array($value,$voteModeUsed) || $value == 1) {
                $error ++;
                continue;
            }else{
                if(app($this->voteModeRepository)->deleteById($value)){

                }else{
                    $error ++;
                }
            }
        }
        if($error == 0 ) {
            return true;
        }
        return ['code' => ['0x000003', 'common']];
    }
    /**
     * 删除投票记录
     */
    public function deleteVoteResult($voteId,$logIds)
    {
        if ($voteId == 0 || !$voteId) {
            return ['code' => ['0x249001', 'vote']];
        }
        if (!isset($logIds['logId']) || empty($logIds['logId'])) {
            return ['code' => ['0x249001', 'vote']];
        }
        $voteManageInfo = app($this->voteRepository)->getDetail($voteId);
        $logIds = $logIds['logId'];
        $tableName = 'vote_data_'.$voteId;
        $error = 0;
        $logIdsArray = explode(',', $logIds);
        $submit = $voteManageInfo->submit;
        foreach ($logIdsArray as  $value) {
            //删除记录
            if(app($this->voteLogRepository)->deleteByWhere(['vote_data_id'=>[$value],'vote_id'=>[$voteId]])) {}else{$error++;};
            //删除数据
            if (Schema::hasTable($tableName)) {
                if(DB::table($tableName)->where('id',$value)->delete()) {}else{$error++;};
            }
            $submit = ($submit-1) < 0 ? 0 : $submit-1;
        }
        //更新调查表提交份数
        app($this->voteRepository)->updateData(['submit'=>$submit],['id'=>[$voteId]]);
        return true;
        // if($error == 0 ) {
        //     return true;
        // }
        // return ['code' => ['0x000003', 'common']];
    }
    /**
     * 删除全部投票记录
     */
    public function deleteAllVoteResult($voteId)
    {
        if ($voteId == 0 || !$voteId) {
            return ['code' => ['0x249001', 'vote']];
        }
        $tableName = 'vote_data_'.$voteId;
        //删除记录
        app($this->voteLogRepository)->deleteByWhere(['vote_id'=>[$voteId]]);
        //更新调查表提交份数
        app($this->voteRepository)->updateData(['submit'=>0],['id'=>[$voteId]]);
        //清空数据
        if (Schema::hasTable($tableName)) {
            DB::table($tableName)->truncate();
            return true;
        }else{
            return true;
        }
        return ['code' => ['0x000003', 'common']];
    }
    /**
     * 恢复默认样式
     */
    public function defaultVoteMode($modeId)
    {
        if ($modeId == 0) {
            return ['code' => ['0x249001', 'vote']];
        }
        $defaultData = '<table style="margin-bottom: 10px; color: #000000; font-family: sans-serif; font-size: 12px; line-height: normal;" border="1" width="100%" cellspacing="0" cellpadding="1">
            <tbody>
            <tr class="firstRow">
            <td style="padding: 5px 10px; border-color: #595959;">&nbsp;<span style="color: #228b22;"><strong>'.trans("vote.vote_mode_title").'<br /></strong></span>'.trans("vote.vote_start_time").'：&nbsp;[***VoteBeginTime***]--[***VoteEndTime***]<br />　'.trans("vote.vote_mode_user").'：&nbsp;[***VoteCreator***]</td>
            <td style="padding: 5px 10px; border-color: #595959;" bgcolor="#000099" width="66">
            <p style="margin-right: 0px; margin-bottom: 0px; margin-left: 0px;  padding: 0px; text-align: center;">&nbsp;</p>
            </td>
            </tr>
            <tr>
            <td style="padding: 5px 10px; border-color: #595959;">
            <p style="margin-right: 0px; margin-bottom: 0px; margin-left: 0px;  padding: 0px; text-align: center;"><span style="color: #ff0000; font-size: 24px;">[***VoteTitle***]</span></p>
            <p style="margin: 0cm 0cm 0pt; font-size: 12px;  padding: 0px; text-indent: 26.4pt; line-height: 25pt;"><span style="font-size: 12pt;"><span style="color: #000000;">'.trans("vote.vote_mode_dec").'</span></span></p>
            <p style="margin-right: 0px; margin-bottom: 0px; margin-left: 0px;  padding: 0px; text-align: center;">&nbsp;</p>
            </td>
            <td style="padding: 5px 10px; border-color: #595959;" bgcolor="#000099" width="66">&nbsp;</td>
            </tr>
            <tr>
            <td style="padding: 5px 10px; border-color: #595959;" align="left">&nbsp;&nbsp;[***VoteContent***]</td>
            <td style="padding: 5px 10px; border-color: #595959;" bgcolor="#000099" width="66">&nbsp;</td>
            </tr>
            <tr>
            <td style="padding: 5px 10px; border-color: #595959;" bgcolor="#000099" height="30">&nbsp;<a href="http://www.weaver.com.cn/" target="_blank"><em><span style="color: #ffffff;">www.weaver.com.cn</span></em></a></td>
            <td style="padding: 5px 10px; border-color: #595959;" bgcolor="#000099" width="66">&nbsp;</td>
            </tr>
            </tbody>
            </table>';
        if (app($this->voteModeRepository)->updateData(['mode_content' => $defaultData], ['mode_id' => [$modeId]])) {
            return true;
        }
    }
    /**
     * 获取样式数据
     */
    public function getVoteMode($modeId)
    {
        if ($modeId == 0) {
            return ['code' => ['0x249001', 'vote']];
        }
        return app($this->voteModeRepository)->getDetail($modeId);
    }
     /**
     * 获取即将开始的调查
     *
     * @return array 处理后的消息数组
     */
    function voteBeginRemind($interval)
    {
        $start  = date("Y-m-d H:i:s");
        $end    = date("Y-m-d H:i:s", strtotime("+$interval minutes -1 seconds"));

        $list = app($this->voteRepository)->getVoteManageList(['search'=>['start_time'=>[[$start,$end],'between']]]);
        $messages = [];
        foreach ($list as $key => $value) {

            $userId         = $this->getVoteInUsers($value['id']);
            $messages[$key] = [
                'remindMark'   => 'vote-submit',
                'toUser'       => implode(',', $userId),
                'contentParam' => ['voteTitle' => $value['vote_name'], 'userName' => $value['vote_create_info']['user_name']],
                'stateParams'  => ['vote_id' => $value['id']]
            ];
        }
        return $messages;
    }
     /**
     * 自动关闭已结束的调查
     *
     * @return array 处理后的消息数组
     */
    function closeOutTimeVotes()
    {
        return app($this->voteRepository)->closeOutTimeVotes();
    }
}
