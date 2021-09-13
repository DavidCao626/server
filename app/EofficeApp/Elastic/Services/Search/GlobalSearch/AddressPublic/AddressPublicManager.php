<?php


namespace App\EofficeApp\Elastic\Services\Search\GlobalSearch\AddressPublic;


use App\EofficeApp\Elastic\Configurations\Constant;
use App\EofficeApp\Elastic\Services\Search\BaseManager;

class AddressPublicManager extends BaseManager
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
        $this->alias = Constant::PUBLIC_ADDRESS_ALIAS;
        $this->type = Constant::PUBLIC_ADDRESS_INDEX_TYPE;
    }
}