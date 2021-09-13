<?php
namespace App\EofficeApp\Attachment\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Attachment\Entities\AttachmentRelEntity;

class AttachmentRelRepository extends BaseRepository 
{
    public function __construct(AttachmentRelEntity $entity) 
    {
        parent::__construct($entity);
    }
    
    public function getAttachmentRelList($wheres)
    {
        return $this->entity->wheres($wheres)->get();
    }

    public function getAttachmentRelListData($params)
    {
        $default = [
            'fields' => ['*'],
            'page' => 0,
            'limit' => config('eoffice.pagesize'),
            'search' => [],
        ];

        $param = array_merge($default, array_filter($params));
        return $this->entity
            ->select($param['fields'])
            ->wheres($param['search'])
            ->parsePage($param['page'], $param['limit'])
            ->get();
    }

    public function getOneAttachmentRel($wheres)
    {
        return $this->entity->wheres($wheres)->first();
    }
    
    public function getAttachmentTables()
    {
        $list = $this->entity->select(['year'])->groupBy('year')->get();

        $tables = [];
        if (count($list) > 0) {
            foreach ($list as $item) {
                $_list = $this->entity->select(['month'])->where('year', $item->year)->groupBy('month')->get();
                if (count($_list) > 0) {
                    foreach ($_list as $_item) {
                        $tables[] = 'attachment_' . $item->year . '_' . $_item->month;
                    }
                }
            }
        }

        return $tables;
    }
}
