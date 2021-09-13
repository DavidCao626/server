<?php

namespace App\EofficeApp\Webmail\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Webmail\Entities\WebmailMailTagEntity;

/**
 * 邮件文件夹Repository类:提供邮件文件夹相关的数据库操作方法
 *
 * @since  2019-08-30 创建
 */
class WebmailMailTagRepository extends BaseRepository
{
    public function __construct(WebmailMailTagEntity $entity)
    {
        parent::__construct($entity);
    }

    /**
     * 修改或添加标签
     *
     * @param [type] $data
     *
     * @return void
     * @author yuanmenglin
     * @since
     */
    public function updateOrCreate($data)
    {
        // return $this->entity->updateOrCreate($data);
        // 单一标签设置
        $item = $this->entity->where(['mail_id' => $data['mail_id']])->first();
        if ($item){
            return $this->entity->where(['mail_id' => $data['mail_id']])->update(['tag_id' => $data['tag_id']]);
        } else {
            return $this->entity->insert($data);
        }
    }

    public function getNum(array $param = [])
    {
        return $this->getTagsParseWhere($this->entity, $param)->count();
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

    /**
     * 根据邮箱id清楚对应邮件标签
     *
     * @param [type] $outboxId
     *
     * @return boolean
     * @author yuanmenglin
     * @since 
     */
    public function cancelTagByOutbox($outboxId, $all = false)
    {
        $query = $this->entity
        ->leftJoin('webmail_tag', 'webmail_tag.tag_id', '=', 'webmail_mail_tag.tag_id')
        ->leftJoin('webmail_mail', 'webmail_mail.mail_id', '=', 'webmail_mail_tag.mail_id')
        ->where('outbox_id', $outboxId);
        if ($all) {
            return $query->where('tag_creator', '<>', 'admin')->delete();
        } else {
            return $query->delete();
        }
    }
}
