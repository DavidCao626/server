<?php


namespace App\EofficeApp\Elastic\Services\Search\GlobalSearch\Notify;


use App\EofficeApp\Elastic\Configurations\Constant;
use App\EofficeApp\Elastic\Services\Search\BaseManager;

class NotifyManager extends BaseManager
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
        $this->alias = Constant::NOTIFY_ALIAS;
        $this->type = Constant::COMMON_INDEX_TYPE;
    }
}