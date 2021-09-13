<?php

namespace App\EofficeApp\Webmail\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Webmail\Entities\WebmailTagEntity;

/**
 * 邮件标签Repository类
 *
 * @since  2019-08-30 创建
 */
class WebmailTagRepository extends BaseRepository
{
    public function __construct(WebmailTagEntity $entity)
    {
        parent::__construct($entity);
    }

    /**
     * 获取列表
     *
     * @param  array $param 查询条件
     *
     * @return array 查询列表
     *
     * @since  2019-08-30
     */
    public function getTags(array $param = [])
    {
        $default = [
            'fields' => ['*'],
            'search' => [],
            'page' => 1,
            'limit' => config('eoffice.pagesize'),
            'order_by' => ['tag_id' => 'asc'],
        ];

        $param = array_filter($param, function ($var) {
            return $var !== '';
        });

        $param = array_merge($default, $param);
        $creator = $param['search']['tag_creator'] ?? '';
        $query = $this->entity
            ->select($param['fields'])
            ->with(['manyMails' => function ($query) use ($creator) {
                $query->select(['tag_id'])->selectRaw("count('*') as num")
                ->leftJoin('webmail_mail', 'webmail_mail.mail_id', '=', 'webmail_mail_tag.mail_id')
                ->leftJoin('webmail_outbox', 'webmail_outbox.outbox_id', '=', 'webmail_mail.outbox_id')
                ->where(function ($query) use ($creator){
                    $query->where('outbox_creator', $creator)->orWhere('webmail_outbox.is_public', 1);
                })
                ->groupBy('tag_id');
            }]);
        // ->with(['creatorName' => function ($query) {
        //     $query->select(['user_id', 'user_name']);
        // }]);

        $query = $this->getTagsParseWhere($query, $param['search']);

        return $query->orders($param['order_by'])
            ->parsePage($param['page'], $param['limit'])
            ->get()
            ->toArray();
    }

    /**
     * 获取数量
     *
     * @param  array $param  查询条件
     *
     * @return int 查询数量
     *
     * @since  2019-08-30
     */
    public function getNum(array $param = [])
    {
        $where = isset($param['search']) ? $param['search'] : [];
        return $this->getTagsParseWhere($this->entity, $where)->count();
    }

    /**
     * 条件解析
     *
     * @param  array $where  查询条件
     *
     * @return int 查询数量
     *
     * @since  2019-08-30
     */
    public function getTagsParseWhere($query, array $where = [])
    {
        return $query->wheres($where);
    }
}
