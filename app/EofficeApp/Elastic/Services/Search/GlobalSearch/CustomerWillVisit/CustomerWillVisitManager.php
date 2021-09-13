<?php


namespace App\EofficeApp\Elastic\Services\Search\GlobalSearch\CustomerWillVisit;


use App\EofficeApp\Elastic\Configurations\Constant;
use App\EofficeApp\Elastic\Services\Search\BaseManager;

class CustomerWillVisitManager extends BaseManager
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
        $this->alias = Constant::CUSTOMER_WILL_VISIT_ALIAS;
        $this->type = Constant::CUSTOMER_WILL_VISIT_INDEX_TYPE;
    }
}