<?php
namespace App\EofficeApp\Kingdee\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Kingdee\Entities\KingdeeK3TableFlowFieldEntity;

class KingdeeK3TableFlowFieldRepository extends BaseRepository
{
    public function __construct(KingdeeK3TableFlowFieldEntity $entity)
    {
        parent::__construct($entity);
    }

    public function mutipInsert($data)
    {
        $this->entity->insert($data);
    }

    public function getFieldList($data)
    {
        if(empty($data['k3_table_flow_id'])){
            return false;
        }
        $res = $this->entity->where('k3_table_flow_id',$data['k3_table_flow_id'])->get();
        return $res ? $res->toArray() : '';
    }

    public function multiDelete($kingdeeTableFlowId)
    {
        if(empty($kingdeeTableFlowId)){
            return false;
        }
        $res = $this->entity->where('k3_table_flow_id',$kingdeeTableFlowId)->delete();
        return true;
    }

    public function DeleteByIds($TableFlowIds)
    {
        if(is_array($TableFlowIds) && count($TableFlowIds) > 0){
            $res = $this->entity->whereIn('k3_table_flow_id',$TableFlowIds)->delete();
            return true;
        }else{
            return false;
        }
        
        
    }


}
