<?php


namespace App\EofficeApp\Elastic\Services\Search\GlobalSearch\PersonnelFiles;


use App\EofficeApp\Elastic\Configurations\Constant;
use App\EofficeApp\Elastic\Services\Search\BaseBuilder;
use App\EofficeApp\PersonnelFiles\Entities\PersonnelFilesEntity;
use Illuminate\Support\Facades\Log;

class PersonnelFilesBuilder extends BaseBuilder
{
    /**
     * @param PersonnelFilesEntity $entity
     */
    public $entity;

    /**
     * @param PersonnelFilesManager $manager
     */
    public $manager;

    /**
     * @param string $alias
     */
    public $alias;

    public function __construct()
    {
        parent::__construct();
        $this->entity = 'App\EofficeApp\PersonnelFiles\Entities\PersonnelFilesEntity';
        $this->manager =  'App\EofficeApp\Elastic\Services\Search\GlobalSearch\PersonnelFiles\PersonnelFilesManager';
        $this->alias = Constant::PERSONNEL_FILES_ALIAS;
    }

    /**
     * 获取对应的entity
     *
     * @param int $id
     *
     * @return PersonnelFilesEntity|null
     */
    public function getRebuildEntity($id)
    {
        /** @var PersonnelFilesEntity $personnelEntity */
        $personnelEntity = app($this->entity);
        $personnel = $personnelEntity->where('id', $id)->first();

        return $personnel;
    }

    /**
     * 生成人事档案索引信息
     *
     * @param PersonnelFilesEntity $personnelEntity
     *
     * @return array
     */
    public function generateDocument(PersonnelFilesEntity $personnelEntity, $targetIndex = null, $isUpdated = false)
    {
        try {
            $attachment = $this->getAttachmentInfo('personnel_files', $personnelEntity->id, $isUpdated);
            $document = [
                'id' => $personnelEntity->id,
                'user_id' => $personnelEntity->user_id,
                'user_name' => $personnelEntity->user_name,
                'home_tel' => $personnelEntity->home_tel,
                'email' => $personnelEntity->email,
                'no' => $personnelEntity->no,
                'create_time' => $personnelEntity->created_at->format('Y-m-d H:i:s'),
                'category' => Constant::PERSONNEL_FILES_CATEGORY,
                'attachment' => $attachment,
            ];
            $document['priority'] = self::getPriority(Constant::PERSONNEL_FILES_CATEGORY);

            $param['index'] = [
                '_index' => $targetIndex ?:$this->alias,
                '_type' => $this->type,
                '_id' => $personnelEntity->id,
            ];

            $param['document'] = $document;

            return $param;
        } catch (\Exception $exception) {
            Log::error($exception->getMessage());
            Log::error($exception->getTraceAsString());
            return [];
        }
    }
}