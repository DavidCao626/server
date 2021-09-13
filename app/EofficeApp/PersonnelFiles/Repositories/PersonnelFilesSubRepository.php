<?php

namespace App\EofficeApp\PersonnelFiles\Repositories;

/**
 * personnel_files_sub资源库
 *
 * @author  朱从玺
 *
 * @since  2015-10-28 创建
 */
class PersonnelFilesSubRepository
{
	/**
	 * [$entity 数据表实体]
	 * 
	 * @var [type]
	 */
	protected $entity;

	public function __construct()
	{
		if(class_exists('App\EofficeApp\PersonnelFiles\Entities\PersonnelFilesSubEntity')) {
			$this->entity = new \App\EofficeApp\PersonnelFiles\Entities\PersonnelFilesSubEntity;
		}else {
			$this->entity = "";
		}
	}
	
	/**
	 * [insertData 插入人事档案自定义字段]
	 *
	 * @author 朱从玺
	 *
	 * @param  [array]     $data [自定义字段数据]
	 *
	 * @since  2015-10-28 创建
	 *
	 * @return [bool]            [插入结果]
	 */
	public function insertData($data)
	{
		if(is_object($this->entity)) {
			return $this->entity->create($data);
		}
	}
	
	/**
	 * [getOneCustom 获取某条数据]
	 *
	 * @author 朱从玺
	 *
	 * @param  [int]     $personnelFileId [人事档案ID]
	 *
	 * @since  2015-10-28 创建
	 *
	 * @return [object]                   [查询结果]
	 */
	public function getOneCustom($personnelFileId, $fields = '')
	{
		if(is_object($this->entity)) {
			if($fields) {
				$this->entity = $this->entity->select($fields);
			}

			return $this->entity->find($personnelFileId);
		}
	}
	
	/**
	 * [modifyCustom 编辑某条数据]
	 *
	 * @author 朱从玺
	 *
	 * @param  [int]         $personnelFileId [人事档案ID]
	 * @param  [array]       $data            [新数据]
	 *
	 * @since  2015-10-28 创建
	 *
	 * @return [bool]                         [编辑结果]
	 */
	public function modifyCustom($personnelFileId, $data)
	{
		if(is_object($this->entity)) {
			return $this->entity->find($personnelFileId)->update($data);
		}
	}
	
	/**
	 * [customDelete 删除某条数据]
	 *
	 * @author 朱从玺
	 *
	 * @param  [int]       $personnelFileId [人事档案ID]
	 *
	 * @since  2015-10-28 创建
	 *
	 * @return [bool]                        [删除结果]
	 */
	public function customDelete($personnelFileId)
	{
		if(is_object($this->entity)) {
			$personnelCustomData = $this->getOneCustom($personnelFileId);

			if($personnelCustomData) {
				return $personnelCustomData->delete();
			}else {
				return true;
			}
			
		}
	}
}