<?php
namespace App\EofficeApp\Attachment\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Attachment\Entities\AttachmentRelSearchEntity;

class AttachmentRelSearchRepository extends BaseRepository
{
    private $orderBy = ['rel_id' => 'desc'];

    public function __construct(AttachmentRelSearchEntity $entity)
    {
        parent::__construct($entity);
    }

    public function getAttachmentList($param)
    {
        $param = $this->filterParam($param);

        $query = $this->entity->select($param['fields']);

        if(isset($param['search']) && !empty($param['search'])){
            $query->wheres($param['search']);
        }

        $query->orders($param['order_by']);

        if($param['page'] == 0){
            return $query->get();
        }

        if(isset($param['parsePage'])){
            $query->parsePage($param['page'], $param['limit']);
        }

        return $query->get();
    }
    public function filterParam($param)
    {
        $param['fields']	= $this->defaultValue('fields',$param, ['*']);

		$param['limit']		= $this->defaultValue('limit',$param, 100);

		$param['page']		= $this->defaultValue('page',$param, 1);

		$param['order_by']	= $this->defaultValue('order_by',$param, $this->orderBy);

        return $param;
    }
    public function defaultValue($key, $data, $default = '')
	{
		return isset($data[$key]) ? $data[$key] : $default;
	}
}
