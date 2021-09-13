<?php 
			namespace App\EofficeApp\PersonnelFiles\Entities;
			use App\EofficeApp\Base\BaseEntity;
			class PersonnelFilesSubEntity  extends BaseEntity
			{
				public $primaryKey 	= 'field_id';
				public $table 			= 'custom_fields_table';
			}