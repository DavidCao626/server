<?php
namespace App\EofficeApp\HtmlSignature\Repositories;

use App\EofficeApp\HtmlSignature\Entities\GoldgridSignatureKeysnEntity;
use App\EofficeApp\Base\BaseRepository;
use DB;
/**
 * 金格签章keysn表
 *
 * @author 丁鹏
 *
 * @since  2015-10-16 创建
 */
class GoldgridSignatureKeysnRepository extends BaseRepository
{
    public function __construct(GoldgridSignatureKeysnEntity $entity)
    {
        parent::__construct($entity);
    }

    /**
     * 获取单个详情
     *
     * @method getUserKeysnDetail
     *
     * @param  [type]                  $termId [description]
     *
     * @return [type]                          [description]
     */
    function getUserKeysnDetail($user_id)
    {
        return $this->entity
                    ->where("user_id",$user_id)
                    ->first();
    }

    /**
     * 获取数据
     *
     * @method getList
     *
     * @param  [type]  $param [description]
     *
     * @return [type]          [description]
     */
    function getList($param)
    {
        $default = [
            'fields'    => ['goldgrid_signature_keysn.*',"user.user_name"],
            'page'      => 0,
            'limit'     => config('eoffice.pagesize'),
            'search'    => [],
            'order_by'  => ['user.list_number' => 'ASC', 'user.user_id' => 'ASC'],
            'returntype' => 'array',
        ];
        $param = array_merge($default, array_filter($param));
        $query = $this->entity
                        ->select($param['fields'])
                        // ->wheres($param['search'])
                        ->multiWheres($param['search'])
                        ->orders($param['order_by'])
                        ->leftJoin('user', 'user.user_id', '=', 'goldgrid_signature_keysn.user_id')
                        ;
        // 分组参数
        if(isset($param['groupBy'])) {
            $query = $query->groupBy($param['groupBy']);
        }
        // 解析原生 where
        if(isset($param['whereRaw'])) {
            foreach ($param['whereRaw'] as $key => $whereRaw) {
                $query = $query->whereRaw($whereRaw);
            }
        }
        // 解析原生 select
        if(isset($param['selectRaw'])) {
            foreach ($param['selectRaw'] as $key => $selectRaw) {
                $query = $query->selectRaw($selectRaw);
            }
        }
        $query = $query->parsePage($param['page'], $param['limit']);
        // 返回值类型判断
        if($param["returntype"] == "array") {
            return $query->get()->toArray();
        } else if($param["returntype"] == "count") {
            return $query->count();
        } else if($param["returntype"] == "object") {
            return $query->get();
        }
    }


    /**
     * 获取列表数量
     *
     * @method getListTotal
     *
     * @param  array                 $param [description]
     *
     * @return [type]                       [description]
     */
    function getListTotal($param = [])
    {
        $param["page"]       = "0";
        $param["returntype"] = "count";
        return $this->getList($param);
    }
}
