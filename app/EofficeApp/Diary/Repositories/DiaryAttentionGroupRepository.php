<?php

namespace App\EofficeApp\Diary\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Diary\Entities\DiaryAttentionGroupEntity;
use Illuminate\Support\Facades\DB;

/**
 * 微博关注组Repository类:提供微博关注组表操作
 *
 * @author lixuanxuan
 *
 * @since  2018-11-13 创建
 */
class DiaryAttentionGroupRepository extends BaseRepository
{
    public function __construct(DiaryAttentionGroupEntity $entity)
    {
        parent::__construct($entity);
    }

    /**
     * 获取微博关注人数量
     *
     * @param  array $param  查询条件
     *
     * @return int 查询数量
     *
     * @author lixuanxuan
     *
     * @since  2018-11-13
     */
    public function getAttentionGroupTotal(array $param = [])
    {
        return $this->entity->wheres($param['search'])->count();
    }

    /**
     * 获取关注分组
     * @param array $param
     * @return mixed
     */
    public function getAttentionGroup(array $param=[])
    {
        return $query = $this->entity->wheres($param['search'])
            ->with(['users'=> function($query) use($param){
                $query->select(['group_id','user_id']);
            }])
            ->orderBy('field_sort')
            ->get()->toArray();
    }

    /**
     * 获取被关注人的分组信息
     * @param $attentionUserId  关注人
     * @param $attentionToUserId  被关注人
     * @return mixed
     */
    public function getAttentionGroupByUserId($attentionUserId,$attentionToUserId)
    {
        return $query = $this->entity
            ->where('diary_attention_group.user_id',$attentionUserId)
            ->leftJoin('diary_attention_group_users','diary_attention_group.group_id','=','diary_attention_group_users.group_id')
            ->where('diary_attention_group_users.user_id',$attentionToUserId)
            ->orderBy('field_sort')
            ->get()->toArray();
    }

}
