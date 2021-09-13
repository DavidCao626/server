<?php

namespace App\EofficeApp\Vote\Repositories;

use App\EofficeApp\Vote\Entities\VoteLogEntity;
use App\EofficeApp\Base\BaseRepository;

/**
 * 调查表知识库
 *
 * @author 史瑶
 *
 * @since  2017-06-21 创建
 */
class VoteLogRepository extends BaseRepository {

    public function __construct(VoteLogEntity $entity) {
        parent::__construct($entity);
    }
    /** @var int 默认列表条数 */
    private $limit      = 10;
    
    /** @var int 默认列表页 */
    private $page       = 0;
    
    public function getReadList($userId) {
        return $this->entity->select(['vote_id'])->where('user_id',$userId)->groupBy('vote_id')->get();
    }    
    public function getHasDataList() {
    	return $this->entity->select(['vote_id'])->groupBy('vote_id')->get();
    }
}
