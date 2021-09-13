<?php
namespace App\EofficeApp\Contract\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Contract\Entities\ContractTypePermissionsEntity;
use DB;
/**
 * 合同信息
 * @author linlm
 * @since  2017-12-13
 */
class ContractTypePermissionRepository extends BaseRepository
{

    public function __construct(ContractTypePermissionsEntity $entity)
    {
        parent::__construct($entity);
    }

    public function getLists($params,$fields = null){
        $query = $this->entity;
        if($fields){
            $query = $query->select($fields);
        }

        if(isset($params['type_id'])){
            $type_ids = $params['type_id'];
            unset($params['type_id']);
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
        return $query->wheres($params)->get();
    }

    public function getTypeDetail($type_id){
        return $this->entity->where('type_id',$type_id)->first();
    }


    public function deleteTypePermissions($id){
        return $this->entity->where('type_id',$id)->delete();
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
                            $query->orWhereRaw('FIND_IN_SET(?,type_id)', [$type_id]);
                        }
                    }else{
                        $query->orWhereRaw('FIND_IN_SET(?,type_id)', [$type_ids]);
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
