<?php
namespace App\EofficeApp\System\SystemMailbox\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\System\SystemMailbox\Entities\WebmailEmailboxEntity;

/**
 * 系统邮箱表知识库
 *
 * @author 丁鹏
 *
 * @since  2015-10-16 创建
 */
class WebmailEmailboxRepository extends BaseRepository
{
    public function __construct(WebmailEmailboxEntity $entity) {
        parent::__construct($entity);
    }

    /**
     * 获取系统邮箱列表
     *
     * @method webmailEmailboxListRepository
     *
     * @param  [type]                        $param [description]
     *
     * @return [type]                               [description]
     */
    function webmailEmailboxListRepository($param) {
        $default = [
            'fields'     => ["*"],
            'page'       => 0,
            'limit'      => config('eoffice.pagesize'),
            'search'     => [],
            'order_by'   => ['emailbox_id'=>'ASC'],
            'returntype' => 'array',
        ];
        $param = array_merge($default, $param);
        $query = $this->entity;
        $query = $query->select($param['fields'])
                    ->wheres($param['search'])
                    ->orders($param['order_by']);
        // 翻页判断
        $query = $query->parsePage($param['page'], $param['limit']);
        // 返回值类型判断
        if($param["returntype"] == "array") {
            return $query->get()->toArray();
        } else if($param["returntype"] == "count") {
            return $query->get()->count();
        } else if($param["returntype"] == "object") {
            return $query->get();
        }
    }

    /**
     * 获取系统邮箱列表数量
     *
     * @method webmailEmailboxListRepositoryGetTotal
     *
     * @param  array                                 $param [description]
     *
     * @return [type]                                       [description]
     */
    function webmailEmailboxListRepositoryGetTotal($param) {
        $param["page"]       = "0";
        $param["returntype"] = "count";
        return $this->webmailEmailboxListRepository($param);
    }
    /*
     * 获取系统邮箱详情
     */
    public function getWebmailEmailboxByWhere($where)
    {
        return $this->entity->wheres($where)->first();
    }

    public function getSetMail() {
        return $this->entity->where('is_default', 1)->first();
    }
}
