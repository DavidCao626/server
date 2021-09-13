<?php


namespace App\EofficeApp\Elastic\Services\Search\GlobalSearch\News;


use App\EofficeApp\Elastic\Configurations\Constant;
use App\EofficeApp\Elastic\Services\Search\BaseManager;

class NewsManager extends BaseManager
{
    /**
     * @param string $alias
     */
    public $alias;

    /**
     * @param string $type
     */
    public $type;

    public function __construct()
    {
        parent::__construct();
        $this->alias = Constant::NEWS_ALIAS;
        $this->type = Constant::NEWS_INDEX_TYPE;
    }
}