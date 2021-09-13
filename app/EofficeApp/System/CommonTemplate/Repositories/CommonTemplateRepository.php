<?php

namespace App\EofficeApp\System\CommonTemplate\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\System\CommonTemplate\Entities\CommonTemplateEntity;
use Schema;

/**
 * 公共模板Repository类:提供公共模板表操作资源
 *
 * @author qishaobo
 *
 * @since  2016-01-22 创建
 */
class CommonTemplateRepository extends BaseRepository
{
    public function __construct(CommonTemplateEntity $entity)
    {
        parent::__construct($entity);
    }

    /**
     * 获取公共模板列表
     *
     * @param  array  $param  查询参数
     *
     * @return array  公共模板列表
     *
     * @author qishaobo
     *
     * @since  2016-01-22
     */
    public function getCommonTemplateList(array $param = [])
    {
        $default = [
            'fields'    => ['template_id', 'template_name', 'template_picture', 'template_description'],
            'page'      => 0,
            'limit'     => config('eoffice.pagesize'),
            'search'    => [],
            'order_by'  => ['template_id' => 'desc'],
        ];

        $param = array_merge($default, array_filter($param));
        if(isset($param['include_content']) && $param['include_content']) {
            $param['fields'] = ['*'];
        }

//        if(Schema::hasTable('attachment_relataion_common_template')) {
//            $query = $this->entity->select(['common_template.*', 'attachment.affect_attachment_name', 'attachment.attachment_base_path', 'attachment.attachment_path', 'attachment.attachment_name', 'attachment.thumb_attachment_name'])
//                                  ->leftJoin('attachment_relataion_common_template', 'common_template.template_id', '=' ,'attachment_relataion_common_template.entity_id')
//                                  ->leftJoin('attachment', 'attachment_relataion_common_template.attachment_id', '=', 'attachment.attachment_id');
//        }else{
            $query = $this->entity->select($param['fields']);
//        }

        $query = $this->parseCommonTemplateWhere($query, $param['search'])->orders($param['order_by'])->parsePage($param['page'], $param['limit']);

        $query = $query->get()->toArray();
        return $query;
    }

    /**
     * 查询数量
     *
     * @param  array  $param 查询条件
     *
     * @return int    数量
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    public function getCommonTemplateTotal(array $param = [])
    {
        $where = isset($param['search']) ? $param['search'] : [];

        $query = $this->parseCommonTemplateWhere($this->entity, $where);

        return $query->count();
    }

    /**
     * 获取公共模板where条件解析
     *
     * @param  array $where  查询条件
     *
     * @return object
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    public function parseCommonTemplateWhere($query, array $where = [])
    {
        return $query->wheres($where);
    }

}
