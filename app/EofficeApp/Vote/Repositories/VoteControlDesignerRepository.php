<?php

namespace App\EofficeApp\Vote\Repositories;

use App\EofficeApp\Vote\Entities\VoteControlDesignerEntity;
use App\EofficeApp\Base\BaseRepository;
use DB;

/**
 * 调查表知识库
 *
 * @author 史瑶
 *
 * @since  2017-06-21 创建
 */
class VoteControlDesignerRepository extends BaseRepository {

    public function __construct(VoteControlDesignerEntity $entity) {
        parent::__construct($entity);
    }
    /** @var int 默认列表条数 */
    private $limit      = 10;
    
    /** @var int 默认列表页 */
    private $page       = 0;
    
    /**
     * 获取调查表设计器详细
     */
    function getVoteControlDesigner($voteId) {
    	return $query = $this->entity->where('vote_id',$voteId)->orders(['position'=>'asc'])->get();
    }
}
