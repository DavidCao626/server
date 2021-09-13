<?php
namespace App\EofficeApp\Meeting\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Meeting\Entities\MeetingExternalUserTypeInfoEntity;
use DB;
/**
 * @会议室资源库类
 *
 * @author 李志军
 */
class MeetingExternalUserTypeInfoRepository extends BaseRepository
{
	public function __construct(
		MeetingExternalUserTypeInfoEntity $entity
	)
	{
		parent::__construct($entity);
	}

    public function getExternalUserList($param)
    {
        $default = array(
			'fields' => ['*'],
			'page' => 0,
			'limit' => config('eoffice.pagesize'),
			'order_by' => ['created_at' => 'desc'],
			'search' => []
		);

		$param = array_merge($default, $param);
        $query = $this->entity->select($param['fields'])
					->where($param['search'])
					->orders($param['order_by']);
        return $query->get();
    }

    public function getExternalUserTotal($param)
    {
        $search = isset($param['search']) ? $param['search'] : [];

        return $this->entity->wheres($search)->count();
    }
    public function getWifiInfo($where)
    {
        return $this->entity->wheres($where)->first();
    }

    public function userPhoneExists($phone, $userId = false)
    {
        $query = $this->entity->where('external_user_phone', $phone);
        if($userId){
            $query->where('external_user_id', '!=', $userId);
        }

        return $query->count() == 1 ? true : false;
    }
    /**
     * 用户管理--生成下一个 user_id
     *
     * @method getNextUserIdBeforeCreate
     *
     * @return [type]                    [description]
     */
    public function getNextUserIdBeforeCreate() {
        $nextUserIdObject = $this->entity
                ->select(['*'])
                ->whereRaw('LENGTH(external_user_id) = 10')
                ->whereRaw("LEFT(external_user_id,2) = 'WV'")
                ->orderBy('external_user_id', 'desc')
                ->first();
        $nextUserId = "WV00000002";
        if (isset($nextUserIdObject["external_user_id"])) {
            $id_max = abs(str_replace('WV', '', $nextUserIdObject["external_user_id"]));
            $id_max++;
            $nextUserId = 'WV' . str_pad($id_max, 8, '0', STR_PAD_LEFT);
        }
        return $nextUserId;
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
        // $data = $this->entity->select($default['fields'])->where("external_user_type", $typeId)->get()->toArray();
        // dd($data);
        return $this->entity->select($default['fields'])->where("external_user_type", $typeId)->get()->toArray();
    }
}
