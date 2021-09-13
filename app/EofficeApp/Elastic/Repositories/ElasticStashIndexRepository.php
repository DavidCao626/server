<?php


namespace App\EofficeApp\Elastic\Repositories;


use App\EofficeApp\Elastic\Entities\ElasticStashIndexEntity;

class ElasticStashIndexRepository extends ElasticBaseRepository
{
    /**
     * 索引贮藏表
     *
     * @param ElasticStashIndexEntity $entity
     *
     */
    public function __construct(ElasticStashIndexEntity $entity )
    {
        parent::__construct($entity);
    }

    /**
     * 获取指定分类未更新的索引
     *
     * @param string $category
     */
    public function getTargetStashIndexByCategory($category)
    {
        return $this->entity->where('category', $category)->pluck('index_id')->toArray();
    }

    /**
     * 删除指定分类的索引
     *
     * @param string $categor
     */
    public function deleteTargetIndexByCategory($category)
    {
        $this->entity->where('category', $category)->delete();
    }
}