<?php

namespace App\EofficeApp\Vote\Repositories;

use App\EofficeApp\Vote\Entities\VoteVersionEntity;
use App\EofficeApp\Base\BaseRepository;
use DB;

/**
 * 调查表知识库
 *
 * @author 史瑶
 *
 * @since  2017-06-21 创建
 */
class VoteVersionRepository extends BaseRepository {

    public function __construct(VoteVersionEntity $entity) {
        parent::__construct($entity);
    }
    /** @var int 默认列表条数 */
    private $limit      = 10;
    
    /** @var int 默认列表页 */
    private $page       = 0;
    
}
