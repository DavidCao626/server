<?php


namespace App\EofficeApp\Elastic\Services\Search\GlobalSearch\Email;


use App\EofficeApp\Elastic\Configurations\Constant;
use App\EofficeApp\Elastic\Services\Search\BaseManager;

class EmailManager extends BaseManager
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
        $this->alias = Constant::EMAIL_ALIAS;
        $this->type = Constant::EMAIL_INDEX_TYPE;
    }
}