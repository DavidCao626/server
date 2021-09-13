<?php

namespace App\EofficeApp\Customer\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Customer\Entities\ContactRecordCommentEntity;

class ContactRecordCommentRepository extends BaseRepository
{
    public function __construct(ContactRecordCommentEntity $entity)
    {
        parent::__construct($entity);
    }

    public function lists(array $params = [])
    {
        $default = [
            'search'   => [],
            'page'     => 0,
            'limit'    => config('eoffice.pagesize'),
            'order_by' => ['comment_id' => 'desc'],
        ];
        $params = array_merge($default, array_filter($params));
        return $this->entity->select('*')->with(['commentHasOneUser' => function ($query) {
            $query->select(['user_id', 'user_name']);
        }])->with('commentHasOneParent')->wheres($params['search'])->orders($params['order_by'])->forPage($params['page'], $params['limit'])->get()->toArray();
    }

    public function total(array $params = [])
    {
        $where = isset($params['search']) ? $params['search'] : [];
        return $this->entity->wheres($where)->count();
    }
}
