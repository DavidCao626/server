<?php


namespace App\EofficeApp\Elastic\Services\Search\GlobalSearch\CustomerLinkman;


use App\EofficeApp\Elastic\Configurations\Constant;
use App\EofficeApp\Elastic\Services\Search\BaseManager;


class CustomerLinkmanManager extends BaseManager
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
        $this->alias = Constant::CUSTOMER_LINKMAN_ALIAS;
        $this->type = Constant::CUSTOMER_LINKMAN_INDEX_TYPE;
    }
}