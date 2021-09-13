<?php

namespace App\EofficeApp\Archives\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Archives\Entities\ArchivesAppraisalEntity;

/**
 * 档案审批Repository类:提供档案审批相关表操作资源
 *
 * @author qishaobo
 *
 * @since  2015-10-21 创建
 */
class ArchivesAppraisalRepository extends BaseRepository
{

    public function __construct(ArchivesAppraisalEntity $entity)
    {
        parent::__construct($entity);

		//if (class_exists('App\EofficeApp\Archives\Entities\ArchivesAppraisalSubEntity')) {
			//$this->archivesAppraisalSubEntity = new \App\EofficeApp\Archives\Entities\ArchivesAppraisalSubEntity();
		//}
    }

    /**
     * 插入鉴定附表数据
     *
     * @param  array        $data  插入数据,一维或二维数组(多条)
     *
     * @return bool|object  插入多条返回是否成功|插入一条返回插入数据对象
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    public function insertSubAppraisal(array $data)
    {
    	if (isset($this->archivesAppraisalSubEntity)) {
       	 	return  $this->archivesAppraisalSubEntity->create($data);
    	}
    	return false;
    }

    /**
     * 获取档案鉴定数据
     *
     * @param  array        $where  查询条件
     *
     *
     * @return bool|object  操作是否成功|查询数据对象
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    public function getAppraisalDetail($where)
    {
    	return $this->entity->wheres($where)->with('hasOneUser')->first();
    }

    /**
     * 获取档案鉴定附表数据
     *
     * @param  int $id 		主键值
     *
     * @return bool|object  操作是否成功|查询数据对象
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    public function getSubAppraisal($id)
    {
    	if (isset($this->archivesAppraisalSubEntity)) {
       	 	return  $this->archivesAppraisalSubEntity->where('archives_appraisal_id', $id)->first();
    	}
    	return false;
    }
}