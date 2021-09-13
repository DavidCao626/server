<?php


namespace App\EofficeApp\Elastic\Services\Search\GlobalSearch\Customer;


use App\EofficeApp\Elastic\Configurations\Constant;
use App\EofficeApp\Elastic\Services\Search\BaseManager;


class CustomerManager extends BaseManager
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
        $this->alias = Constant::CUSTOMER_ALIAS;
        $this->type = Constant::CUSTOMER_INDEX_TYPE;
    }
}