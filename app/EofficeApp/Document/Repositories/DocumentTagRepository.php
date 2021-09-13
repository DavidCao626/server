<?php

namespace App\EofficeApp\Document\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Document\Entities\DocumentTagEntity;

/**
 * 文档标签Repository类:提供文档标签相关的数据库操作方法。
 *
 * @author niuxiaoke
 *
 * @since  2017-08-01 创建
 */
class DocumentTagRepository extends BaseRepository
{

    public function __construct(DocumentTagEntity $entity)
    {
        parent::__construct($entity);
    }

    /**
     * 获取文档标签
     *
     * @param  array $where 查询条件
     *
     * @return array 查询列表
     *
     * @author niuxiaoke
     *
     * @since  2017-08-01
     */
    function getDocumentTags($where, $with = false) {
        $query = $this->entity
        ->select('document_tag.tag_id')
        ->leftJoin('tag', 'document_tag.tag_id', '=', 'tag.tag_id')
        ->wheres($where);

        if ($with) {
            $query = $query->selectRaw("count('document_id') as document_num")
            ->with(['hasOneTag' => function ($query) {
                $query->select(['tag_id', 'tag_name']);
            }])->groupBy('tag_id');
        }

        return $query->get()->toArray();
    }

    /**
     * 获取文档标签
     *
     * @param  array $where 查询条件
     *
     * @return array 查询列表
     *
     * @author niuxiaoke
     *
     * @since  2017-08-01
     */
    function getTagName($tagId, $documentId)
    {
        return $query = $this->entity
                              ->select('tag_name')
                              ->leftJoin('tag', 'document_tag.tag_id', '=', 'tag.tag_id')
                              ->whereIn('document_tag.tag_id', $tagId)
                              ->where('document_id', $documentId)
                              ->get()
                              ->toArray();
    }

    public function getDocumentViewTag($param)
    {
        $query = $this->entity
                    ->select('document_tag.tag_id')
                    ->leftJoin('tag', 'document_tag.tag_id', '=', 'tag.tag_id')
                    ->wheres($param['search'])
                    ->orWhere(function($query) use ($param){
                        $query->wheres($param['orSearch']);
                    });

        return $query->get()->toArray();
    }

    public function getTagIdByDocumentId($id){
        return $this->entity
            ->select('tag_id')
            ->where('document_id', $id)
            ->get()
            ->toArray();
    }
}