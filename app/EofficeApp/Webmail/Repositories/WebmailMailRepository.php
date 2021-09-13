<?php

namespace App\EofficeApp\Webmail\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Webmail\Entities\WebmailMailEntity;

/**
 * 邮件Repository类:提供邮件相关的数据库操作方法。
 *
 * @author qishaobo
 *
 * @since  2016-07-28 创建
 */
class WebmailMailRepository extends BaseRepository
{
    public function __construct(WebmailMailEntity $entity)
    {
        parent::__construct($entity);
        $this->webmailMailTagRepository = 'App\EofficeApp\Webmail\Repositories\WebmailMailTagRepository';
        $this->webmailFolderRepository   = 'App\EofficeApp\Webmail\Repositories\WebmailFolderRepository';
    }
    /**
     * 获取邮件列表
     *
     * @param  array $param 查询条件
     *
     * @return array 查询列表
     *
     * @author qishaobo
     *
     * @since  2016-07-28
     */
    public function getMails(array $param = [])
    {
        $default = [
            'fields'    => ['*'],
            'search'    => [],
            'page'      => 1,
            'limit'     => config('eoffice.pagesize'),
            'order_by'  => ['mail_id' => 'asc'],
        ];
        $param = array_merge($default, array_filter($param));
        $query = $this->entity
        ->with(['tags' => function ($query) {
            $query->orderBy('webmail_tag.updated_at', 'desc');
        }])
        ->select($param['fields']);
        $query = $this->getMailsParseWhere($query, $param['search']);

        return $query->orders($param['order_by'])
            ->parsePage($param['page'], $param['limit'])
            ->get()
            ->toArray();
    }

    /**
     * 获取邮件数量
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
        return  $this->getMailsParseWhere($this->entity, $where)->count();
    }

    /**
     * 获取邮件条件解析
     *
     * @param  array $where  查询条件
     *
     * @return int 查询数量
     *
     * @author qishaobo
     *
     * @since  2016-07-28
     */
    public function getMailsParseWhere($query, array $where = [])
    {
        // 高级搜索的标签名
        if (isset($where['tag_name']) && !empty($where['tag_name'])){
            $query = $query->leftJoin('webmail_mail_tag', 'webmail_mail_tag.mail_id', '=', 'webmail_mail.mail_id')
            ->leftJoin('webmail_tag', 'webmail_mail_tag.tag_id', '=', 'webmail_tag.tag_id');
        }
        // 检测有无标签查询
        if (isset($where['tag']) && !empty($where['tag'])){
            $tagId = $where['tag'];
            unset($where['tag']);
        }
        // 标签查询的特殊处理
        if (isset($tagId) && !empty($tagId)) {
            $mails = app($this->webmailMailTagRepository)->getFieldInfo(['tag_id' => $tagId]);
            if ($mails) {
                $mailIds = array_column($mails, 'mail_id');
                $where['mail_id'] = [$mailIds, 'in'];
                unset($where['folder']);
            } else {
                // 未查到邮件id
                $where['mail_id'] = [0];
            }            
        }
        $folder = $where['folder'] ?? [];
        if (empty($folder)) {
            unset($where['folder']);
        }
        return $query->wheres($where);
    }

    public function removeFolderByOutbox($outboxId, $all = false)
    {
        $query = $this->entity
        ->leftJoin('webmail_folder', 'webmail_folder.folder_id', '=', 'webmail_mail.folder')
        ->where('folder', '>', 0)
        ->where('webmail_mail.outbox_id', $outboxId);
        if ($all) {
            $query = $query->delete();
        } else {
            $query = $query->where('webmail_folder.folder_creator', '<>', 'admin')->delete();
        }
    }

    /**
     * @param int   $id
     * @param bool  $withTrashed
     * @param array $fields
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function getDetail($id, $withTrashed = false, array $fields = ['*'])
    {
        $query = $this->entity->select($fields)->with(['outbox' => function($query){
            $query->select(['is_public', 'outbox_id']);
        }]);
        return $query->find($id);
    }
}