<?php 

namespace App\EofficeApp\Archives\Entities;

use App\EofficeApp\Base\BaseEntity;

/**
 * 档案审核附表Entity类:提供档案审核附表实体
 *
 * @author qishaobo
 *
 * @since  2015-10-21 创建
 */
class ArchivesAppraisalSubEntity extends BaseEntity
{
	public $primaryKey 	= 'archives_appraisal_id';
	public $table 	    = 'archives_appraisal_sub';
}