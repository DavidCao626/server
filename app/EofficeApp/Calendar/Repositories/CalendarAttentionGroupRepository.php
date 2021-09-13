<?php

namespace App\EofficeApp\Calendar\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Calendar\Entities\CalendarAttentionGroupEntity;
use Illuminate\Support\Facades\DB;

/**
 * 日程关注组Repository类
 *
 * @author lixu
 *
 * @since  2018-11-13 创建
 */
class CalendarAttentionGroupRepository extends BaseRepository
{
    public function __construct(CalendarAttentionGroupEntity $entity)
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

        $query =  $this->entity;
        if (isset($param['search'])) {
            $query = $query->wheres($param['search']);
        }
        return $query->count();
    }

    /**
     * 获取关注分组
     * @param array $param
     * @return mixed
     */
    public function getAttentionGroup(array $param=[])
    {
        $query = $this->entity;
        if (isset($param['search'])) {
            $query = $query->wheres($param['search']);
        }
        return $query->with(['users'=> function($query) use($param){

                $query->select(['group_id','attention_person', 'attention_to_person', 'is_read']);
                if (!isset($param['type'])) {
                    $query->where('attention_status', 2);
                }
            }])
            ->orderBy('field_sort')
            ->orderBy('group_id', 'asc')
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
            ->where('calendar_attention_group.user_id',$attentionUserId)
            ->leftJoin('calendar_attention','calendar_attention_group.group_id','=','calendar_attention.group_id')
            ->where('calendar_attention.attention_to_person',$attentionToUserId)
            ->where('calendar_attention.attention_person',$attentionUserId)
            ->orderBy('field_sort')
            ->get()->toArray();
    }
}
