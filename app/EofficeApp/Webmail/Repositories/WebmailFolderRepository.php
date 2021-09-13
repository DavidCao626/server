<?php

namespace App\EofficeApp\Webmail\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Webmail\Entities\WebmailFolderEntity;

/**
 * 邮件文件夹Repository类:提供邮件文件夹相关的数据库操作方法。
 *
 * @author qishaobo
 *
 * @since  2016-07-28 创建
 */
class WebmailFolderRepository extends BaseRepository
{
    public function __construct(WebmailFolderEntity $entity)
    {
        parent::__construct($entity);

    }

    /**
     * 获取文件夹列表
     *
     * @param  array $param 查询条件
     *
     * @return array 查询列表
     *
     * @author qishaobo
     *
     * @since  2016-07-28
     */
    public function getFolders(array $param = [])
    {
        $default = [
            'fields'   => ['*'],
            'search'   => [],
            'page'     => 1,
            'limit'    => config('eoffice.pagesize'),
            'order_by' => ['folder_id' => 'asc'],
        ];

        $param = array_filter($param, function($var) {
            return $var !== '';
        });

        $param = array_merge($default, $param);
        $creator = $param['search']['folder_creator'] ?? '';
        $query = $this->entity
        ->select($param['fields'])
        ->with(['creatorName' => function ($query) {
            $query->select(['user_id', 'user_name']);
        }])
        ->with(['manyMails' => function ($query) use ($creator) {
            $query->select(['folder'])->selectRaw("count('*') as num")
            ->leftJoin('webmail_outbox', 'webmail_outbox.outbox_id', '=', 'webmail_mail.outbox_id')
            ->where(function($query) use ($creator) {
                $query->where('is_public', 1)->orWhere('outbox_creator', $creator);
            })
            ->groupBy('folder');
        }]);

        $query = $this->getFoldersParseWhere($query, $param['search']);

        return $query->orders($param['order_by'])
            ->parsePage($param['page'], $param['limit'])
            ->get()
            ->toArray();
    }

    /**
     * 获取文件夹数量
     *
     * @param  array $param  查询条件
     *
     * @return int 查询数量
     *
     * @author qishaobo
     *
     * @since  2016-07-28
     */
    public function getNum(array $param = [])
    {
        $where = isset($param['search']) ? $param['search'] : [];
        return  $this->getFoldersParseWhere($this->entity, $where)->count();
    }

    /**
     * 获取文件夹条件解析
     *
     * @param  array $where  查询条件
     *
     * @return int 查询数量
     *
     * @author qishaobo
     *
     * @since  2016-07-28
     */
    public function getFoldersParseWhere($query, array $where = [])
    {
        return $query->wheres($where);
    }
}