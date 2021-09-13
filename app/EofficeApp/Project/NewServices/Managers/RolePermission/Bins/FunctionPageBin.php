<?php

namespace App\EofficeApp\Project\NewServices\Managers\RolePermission\Bins;

use App\EofficeApp\Project\Entities\FunctionPageApiEntity;

class FunctionPageBin
{
    // 暂时存储config中的key，初始化后变成对应值
    private $functionPageId = 'function_page_id';
    private $parentId = 0;
    private $isShow = 1;
    private $isRelated = 2;
    private $filterState = 3;
    public function __construct($functionPageConfig)
    {
        $this->functionPageId = $functionPageConfig[$this->functionPageId];
        $this->parentId = $functionPageConfig[$this->parentId];
        $this->isShow = $functionPageConfig[$this->isShow];
        $this->isRelated = $functionPageConfig[$this->isRelated];
        $this->filterState = $functionPageConfig[$this->filterState];
    }

    /**
     * @return string
     */
    public function getFunctionPageId(): string
    {
        return $this->functionPageId;
    }

    /**
     * @param string $functionPageId
     */
    public function setFunctionPageId(string $functionPageId): void
    {
        $this->functionPageId = $functionPageId;
    }

    /**
     * @return int
     */
    public function getParentId(): int
    {
        return $this->parentId;
    }

    /**
     * @param int $parentId
     */
    public function setParentId(int $parentId): void
    {
        $this->parentId = $parentId;
    }

    /**
     * @return int
     */
    public function getisShow(): int
    {
        return $this->isShow;
    }

    /**
     * @param int $isShow
     */
    public function setIsShow(int $isShow): void
    {
        $this->isShow = $isShow;
    }

    /**
     * @return int
     */
    public function getisRelated(): int
    {
        return $this->isRelated;
    }

    /**
     * @param int $isRelated
     */
    public function setIsRelated(int $isRelated): void
    {
        $this->isRelated = $isRelated;
    }

    /**
     * @return int
     */
    public function getFilterState(): int
    {
        return $this->filterState;
    }

    /**
     * @param int $filterState
     */
    public function setFilterState(int $filterState): void
    {
        $this->filterState = $filterState;
    }


}
