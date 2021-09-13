<?php
namespace App\EofficeApp\Cooperation\Repositories;

use App\EofficeApp\Cooperation\Entities\CooperationPurviewEntity;
use App\EofficeApp\Base\BaseRepository;

/**
 * 协作区权限表表知识库
 *
 * @author 丁鹏
 *
 * @since  2015-10-16 创建
 */
class CooperationPurviewRepository extends BaseRepository
{
    public function __construct(CooperationPurviewEntity $entity) {
        parent::__construct($entity);
    }

    /**
     * 获取有权限用户
     * @param  int $subjectId 协作主题id
     * @return array          有权限的用户id
     */
    function getPermissionUserBySubjectId($subjectId) {
        return $this->entity
                  ->select("user_id")
                  ->where('subject_id',$subjectId)
                  ->get();
    }

    /**
     * 过去某条Purview数据详情
     * @param  int $subjectId 协作主题id
     * @return array          有权限的用户id
     */
    function getCooperationPurviewDetail($param) {
        return $this->entity
                  ->wheres($param)
                  ->first();
    }
    public function updatePurviewData($param, $userId) {
        $subjectId = isset($param['subject_id']) ? $param['subject_id'] : '';
        $purview_time = isset($param['purview_time']) ? $param['purview_time'] : '';
        return $this->entity
                  ->where('user_id', '!=', $userId)
                  ->where('subject_id', '=', $subjectId)
                  ->update(['purview_time' => $purview_time]);
    }

}
