<?php

namespace App\EofficeApp\System\Remind\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\System\Remind\Entities\RemindsEntity;

/**
 * reminds表资源库
 *
 * @author  朱从玺
 *
 * @since  2015-10-28 创建
 */
class RemindsRepository extends BaseRepository
{
	public function __construct(RemindsEntity $entity)
	{
		parent::__construct($entity);
	}

	/**
	 * [getAllReminds 获取所有提醒方式]
	 *
	 * @author 朱从玺
	 *
	 * @since  2015-10-28 创建
	 *
	 * @return [object]     [查询结果]
	 */
	public function getAllReminds()
	{
		return $this->entity->all();
	}

        /**
         * 判断 yww
         */
        public function checkReminds($reminds){
            return $this->entity->where("reminds",$reminds)->first();
        }
}