<?php
namespace App\EofficeApp\Contract\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Contract\Entities\ContractTypeMonitorPermissionsEntity;
use DB;
/**
 * 合同信息
 * @author linlm
 * @since  2017-12-13
 */
class ContractTypeMonitorPermissionRepository extends BaseRepository
{

    public function __construct(ContractTypeMonitorPermissionsEntity $entity)
    {
        parent::__construct($entity);
    }


    public function groupList($param){
        $default = array(
            'fields'   => ['*'],
            'page'     => 0,
            'limit'    => config('eoffice.pagesize'),
            'order_by' => ['id' => 'desc'],
            'search'   => [],
        );
        $query = $this->entity;
        $param = array_merge($default, $param);
        if(isset($param['type_id'])){
            $type_ids = $param['type_id'];
            unset($param['type_id']);
            $query = $query->where(function ($query) use($type_ids){
                $query->orWhere(['type_id'=> 'all']);
                if($type_ids){
                    if(is_array($type_ids)){
                        foreach($type_ids as $type_id){
                            $query->orWhereRaw('FIND_IN_SET(?,type_id)',[$type_id]);
                        }
                    }else{
                        $query->orWhereRaw('FIND_IN_SET(?,type_id)',[$type_ids]);
                    }
                }
            });
        }
        $query = $query->wheres($param['search'])
            ->select($param['fields'])
            ->parsePage($param['page'], $param['limit'])
            ->orders($param['order_by']);

        return $query->get()->toArray();
    }

}
