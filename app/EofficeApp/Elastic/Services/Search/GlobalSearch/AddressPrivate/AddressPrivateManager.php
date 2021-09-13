<?php


namespace App\EofficeApp\Elastic\Services\Search\GlobalSearch\AddressPrivate;


use App\EofficeApp\Elastic\Configurations\Constant;
use App\EofficeApp\Elastic\Services\Search\BaseManager;

class AddressPrivateManager extends BaseManager
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
        $this->alias = Constant::PRIVATE_ADDRESS_ALIAS;
        $this->type = Constant::COMMON_INDEX_TYPE;
    }
}