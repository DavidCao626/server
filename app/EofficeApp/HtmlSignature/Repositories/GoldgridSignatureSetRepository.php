<?php
namespace App\EofficeApp\HtmlSignature\Repositories;

use App\EofficeApp\HtmlSignature\Entities\GoldgridSignatureSetEntity;
use App\EofficeApp\Base\BaseRepository;
use DB;
/**
 * 金格签章设置表
 *
 * @author 丁鹏
 *
 * @since  2015-10-16 创建
 */
class GoldgridSignatureSetRepository extends BaseRepository
{
    public function __construct(GoldgridSignatureSetEntity $entity)
    {
        parent::__construct($entity);
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
    function getSignatureSet($param = [])
    {
        $query = $this->entity;
        if(isset($param["where"])) {
            $query->wheres($param["where"]);
        }
        return $query->get();
    }
}
