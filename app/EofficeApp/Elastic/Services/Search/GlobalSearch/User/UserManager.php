<?php


namespace App\EofficeApp\Elastic\Services\Search\GlobalSearch\User;

use App\EofficeApp\Elastic\Configurations\Constant;
use App\EofficeApp\Elastic\Services\Search\BaseManager;

class UserManager extends BaseManager
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
        $this->alias = Constant::USER_ALIAS;
        $this->type = Constant::USER_INDEX_TYPE;
    }
}