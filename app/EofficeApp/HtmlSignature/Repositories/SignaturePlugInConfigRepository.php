<?php
namespace App\EofficeApp\HtmlSignature\Repositories;

use App\EofficeApp\HtmlSignature\Entities\SignaturePlugInConfigEntity;
use App\EofficeApp\Base\BaseRepository;
use DB;
/**
 * 签章插件配置
 *
 * @author yml
 *
 * @since  2020-07-22 创建
 */
class SignaturePlugInConfigRepository extends BaseRepository
{
    public function __construct(SignaturePlugInConfigEntity $entity)
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
    function getList()
    {
        return $this->entity->get()->toArray();
    }
}
