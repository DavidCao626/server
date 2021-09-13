<?php

namespace App\EofficeApp\Assets\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Assets\Entities\AssetsFormSetEntity;
use DB;

/**
 * 资产类型列表
 *
 * @author zw
 *
 * @since  2018-03-30 创建
 */
class AssetsFormSetRepository extends BaseRepository
{

    public function __construct(AssetsFormSetEntity $entity)
    {
        parent::__construct($entity);
    }

    public static function getDetailData($id = null){
        $query = DB::table('assets_form_set');
        if($id){
            $query->where('id',$id);
        }
        return $query->first();
    }


}