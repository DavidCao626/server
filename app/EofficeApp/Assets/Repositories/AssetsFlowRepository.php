<?php

namespace App\EofficeApp\Assets\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Assets\Entities\AssetsFlowEntity;
use DB;

/**
 * 资产类型列表
 *
 * @author zw
 *
 * @since  2018-03-30 创建
 */
class AssetsFlowRepository extends BaseRepository
{

    public function __construct(AssetsFlowEntity $entity)
    {
        parent::__construct($entity);
    }






    /**
     * 获取关联流程
     *
     * @param  array
     *
     * @return bool|object
     *
     * @author zhangwei
     *
     * @since  2018-03-29
     */

    public function getFlowRuns($run_ids){
        $lists = DB::table('flow_run')->select(['run_id','run_name','flow_id'])->whereIn('run_id',$run_ids)->get();
        return $lists ? :[];
    }

    /**
     * 关联流程
     *
     * @param  array
     *
     * @return bool|object
     *
     * @author zhangwei
     *
     * @since  2018-03-29
     */

    public function insertFlow($run_ids,$assets_id,$type){
        $lists = DB::table('flow_run')->select(['run_name', 'run_id'])->whereIn('run_id', explode(',',$run_ids))->get();
        if($lists && is_object($lists)){
            foreach ($lists as $key => $vo){
                $data[$key]['run_name']  = trim($vo->run_name);
                $data[$key]['run_id']    = intval($vo->run_id);
                $data[$key]['created_at']= date('Y-m-d H:i:s',time());
                $data[$key]['assets_id'] = $assets_id;
                $data[$key]['flow_type'] = $type;
            }
            $this->insertMultipleData($data);
        }
    }

    public function getFlow($run_ids,$assets_id,$type){
        $result = $this->entity->select('*')
            ->where(function ($query) use($type,$assets_id,$run_ids){
                $query->where('flow_type',$type)->where('assets_id',$assets_id)->whereIn('run_id', explode(',',$run_ids));
            })->get();

        $lists = DB::table('flow_run')->select(['run_name', 'run_id'])->whereIn('run_id', explode(',',$run_ids))->get();
    }

}