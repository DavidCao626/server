<?php


namespace App\EofficeApp\Elastic\Services\Search\GlobalSearch\Document;


use App\EofficeApp\Elastic\Configurations\Constant;
use App\EofficeApp\Elastic\Services\Search\BaseManager;

class DocumentManager extends BaseManager
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
        $this->alias = Constant::DOCUMENT_ALIAS;
        $this->type = Constant::DOCUMENT_INDEX_TYPE;
    }
}