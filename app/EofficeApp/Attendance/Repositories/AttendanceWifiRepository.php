<?php
namespace App\EofficeApp\Attendance\Repositories;
use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Attendance\Entities\AttendanceWifiEntity;

/**
 * attendance_out表资源库
 *
 * @author  朱从玺
 *
 * @since  2015-11-10 创建
 */
class AttendanceWifiRepository extends BaseRepository
{
	public function __construct(
		AttendanceWifiEntity $entity
	)
	{
		parent::__construct($entity);
	}
    
    public function getWifiList($param)
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
					->wheres($param['search'])
					->orders($param['order_by']);
		if(!isset($param['noPage'])) {
			$query = $query->parsePage($param['page'], $param['limit']);
		}
        return $query->get();
    }
    
    public function getWifiTotal($param)
    {
        $search = isset($param['search']) ? $param['search'] : [];
        
        return $this->entity->wheres($search)->count();
    }
    public function getWifiInfo($where)
    {
        return $this->entity->wheres($where)->first();
    }
    
    public function wifiMacExists($wifiMac, $wifiId = false)
    {
        $query = $this->entity->where('attend_wifi_mac', $wifiMac);
        if($wifiId){
            $query->where('attend_wifi_id', '!=', $wifiId);
        }
        
        return $query->count() == 1 ? true : false;
    }
}