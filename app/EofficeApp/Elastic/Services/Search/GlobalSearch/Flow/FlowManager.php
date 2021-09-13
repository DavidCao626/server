<?php


namespace App\EofficeApp\Elastic\Services\Search\GlobalSearch\Flow;


use App\EofficeApp\Elastic\Configurations\Constant;
use App\EofficeApp\Elastic\Services\Search\BaseManager;

class FlowManager extends BaseManager
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
        $this->alias = Constant::FLOW_ALIAS;
        $this->type = Constant::FLOW_INDEX_TYPE;
    }
}