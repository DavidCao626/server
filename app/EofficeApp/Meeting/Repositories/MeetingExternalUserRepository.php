<?php
namespace App\EofficeApp\Meeting\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Meeting\Entities\MeetingExternalUserEntity;
use DB;
/**
 * @会议室资源库类
 *
 * @author 李志军
 */
class MeetingExternalUserRepository extends BaseRepository
{
	public function __construct(
		MeetingExternalUserEntity $entity
	)
	{
		parent::__construct($entity);
	}

    public function getExternalUserList($param)
    {
        $default = array(
			'fields' => ['meeting_external_user.*', 'meeting_external_user_type.external_user_type_id', 'meeting_external_user_type.external_user_type_name'],
			'page' => 0,
			'limit' => config('eoffice.pagesize'),
			'order_by' => ['created_at' => 'desc'],
			'search' => []
		);

		$param = array_merge($default, $param);
        $search = isset($param['search']) ? $param['search'] : [];
        if (isset($search['external_user_type']) && ($search['external_user_type'] == 1 || (isset($search['external_user_type'][0]) && $search['external_user_type'][0] == 1 ))) {
            $query = $this->entity->select($param['fields'])
                    ->leftJoin('meeting_external_user_type', function($join) {
                        $join->on('meeting_external_user_type.external_user_type_id', "=","meeting_external_user.external_user_type");
                    })
                    ->where('external_user_type', $search['external_user_type'])
                    ->orWhereNull('external_user_type')
                    ->orders($param['order_by'])
                    ->parsePage($param['page'], $param['limit']);
                    return $query->get();
        }else if(isset($search['external_user_type']) && !empty($search['external_user_type'])) {
            $query = $this->entity->select($param['fields'])
                    ->leftJoin('meeting_external_user_type', function($join) {
                        $join->on('meeting_external_user_type.external_user_type_id', "=","meeting_external_user.external_user_type");
                    })
                    ->where('external_user_type', $search['external_user_type'])
                    ->orders($param['order_by'])
                    ->parsePage($param['page'], $param['limit']);
            return $query->get();
        }else{
            $query = $this->entity->select($param['fields'])
                    ->leftJoin('meeting_external_user_type', function($join) {
                        $join->on('meeting_external_user_type.external_user_type_id', "=","meeting_external_user.external_user_type");
                    })
                    ->wheres($param['search'])
                    ->orders($param['order_by'])
                    ->parsePage($param['page'], $param['limit']);
        return $query->get();
        }

    }

    public function getExternalUserTotal($param)
    {
        $search = isset($param['search']) ? $param['search'] : [];
        if ($search && isset($search['external_user_type'])) {
            $searchType = $search['external_user_type'];
                $query = $this->entity->select(['meeting_external_user.*', 'meeting_external_user_type.external_user_type_id', 'meeting_external_user_type.external_user_type_name'])
                    ->leftJoin('meeting_external_user_type', function($join) {
                        $join->on('meeting_external_user_type.external_user_type_id', "=","meeting_external_user.external_user_type");
                    })
                    ->where('external_user_type', $param['search']['external_user_type'])
                    ->orWhereNull('external_user_type');
                    return $query->count();
            
        }else{
            return $this->entity->select(['meeting_external_user.*', 'meeting_external_user_type.external_user_type_id', 'meeting_external_user_type.external_user_type_name'])
                    ->leftJoin('meeting_external_user_type', function($join) {
                        $join->on('meeting_external_user_type.external_user_type_id', "=","meeting_external_user.external_user_type");
                    })->wheres($search)->count();
        }
    }
    public function getWifiInfo($where)
    {
        return $this->entity->wheres($where)->first();
    }

    public function userPhoneExists($phone, $userId = false)
    {
        $query = $this->entity->where('external_user_phone', $phone)->where('external_user_phone', '!=', '');
        if($userId){
            $query->where('external_user_id', '!=', $userId);
        }

        return $query->count() == 1 ? true : false;
    }
    /**
     * 獲取全部用戶的ID信息
     */
    public function getExternalUserId() {
        return $this->entity->select("external_user_id")->get()->toArray();
    }

    public function getExternalTypeTotalbyId($typeId) {
        return $this->entity->select('*')->where("external_user_type", $typeId)->count();
    }
    public function getExternalUserListById($typeId) {

        $default = array(
            'fields' => ['*'],
            'page' => 0,
            'limit' => config('eoffice.pagesize'),
            'order_by' => ['created_at' => 'desc'],
            'search' => []
        );
        return $this->entity->select($default['fields'])->where("external_user_type", $typeId)->get()->toArray();
    }

    public function updateSignMeeting($userId, $mApplyId, $data) {
        return $this->entity->where('external_user_id', $userId)->where('meeting_apply_id',$mApplyId)->update($data);
    }
    /**
     * 获取外部人员姓名
     * @return [type] [object]
     */
    public function getExternalUserName ($userId, $field = 'external_user_name') {
        if (is_string($userId)) {
            $userId = explode(',', trim($userId, ','));
        }
        $users = DB::table('meeting_external_user')->select([$field])->whereIn('external_user_id', $userId)->get();
        if (count($users) > 0) {
            if (count($users) > 1) {
                $userAttr = '';

                foreach ($users as $user) {
                    $userAttr .= $user->$field . ',';
                }

                return rtrim($userAttr, ',');
            }

            return $users ? $users[0]->$field : '';
        }
        return '';
    }

    public function getNotSignInExternalUser($mApplyId) {
        if(empty($mApplyId)) {
            return ['code' => ['0x000003','common']];
        }
        $user = DB::table("meeting_external_attendance")->select("meeting_external_user")->where("meeting_apply_id", $mApplyId)->where("meeting_sign_status", 0)->get();
        if(count($user) > 0) {
            if (count($user) > 1) {
                $userAttr = '';
                foreach($user as $value) {
                    $userAttr .= $value->meeting_external_user . ',';
                }
                return rtrim($userAttr, ',');
            }
            return $user ? $user[0]->meeting_external_user : '';
        }
        return '';

    }
    function getHaveSignInExternalUser($mApplyId) {
        if(empty($mApplyId)) {
            return ['code' => ['0x000003','common']];
        }
        $user = DB::table("meeting_external_attendance")->select("meeting_external_user")->where("meeting_apply_id", $mApplyId)->where("meeting_sign_status", 1)->get();
        if (count($user) > 0) {
            if (count($user) > 1) {
                $userAttr = '';
                foreach($user as $value) {
                    $userAttr .= $value->meeting_external_user . ',';
                }
                return rtrim($userAttr, ',');
            }
            return $user ? $user[0]->meeting_external_user : '';
        }
        return '';

    }
    function getExternalUserIdString() {
        $data = $this->entity->select("external_user_id")->get()->toArray();
        $userIdList = [];
        if (!empty($data) && is_array($data)) {
            foreach($data as $key => $value) {
                $userIdList[] = $value['external_user_id'];
            }
        }
        return $userIdList;
    }
}
