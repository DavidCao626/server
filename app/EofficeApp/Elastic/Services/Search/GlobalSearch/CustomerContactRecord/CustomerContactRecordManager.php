<?php


namespace App\EofficeApp\Elastic\Services\Search\GlobalSearch\CustomerContactRecord;


use App\EofficeApp\Elastic\Configurations\Constant;
use App\EofficeApp\Elastic\Services\Search\BaseManager;

class CustomerContactRecordManager extends BaseManager
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
        $this->alias = Constant::CUSTOMER_CONTACT_RECORD_ALIAS;
        $this->type = Constant::CUSTOMER_CONTACT_RECORD_INDEX_TYPE;
    }
}