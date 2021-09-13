<?php


namespace App\EofficeApp\Invoice\Repositories;


use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Invoice\Entities\InvoiceManageParamsEntities;

class InvoiceManageParamsRepositories extends BaseRepository
{
    public function __construct(InvoiceManageParamsEntities $entity)
    {
        parent::__construct($entity);
    }

    public function setParams($key, $value)
    {
        if ($this->entity->where('param_key', $key)->count() == 0) {
            $res = $this->entity->where('param_key', $key)->insert(['param_key' => $key, 'param_value' => $value]);
        } else {
            $res = $this->entity->where('param_key', $key)->where('param_key', $key)->update(['param_value' => $value]);
        }
        return $res;
    }

    public function getParam($key = null, $default = '')
    {
        if (is_null($key)) {
            return $this->entity->get();
        }
        if (is_array($key)) {
            return $this->entity->where('param_key', 'in', $key)->get();
        }
        $paramValue = '';
        $param      = $this->entity->where('param_key', $key)->get();
        if (count($param)) {
            $_param     = $param[0];
            $paramValue = $_param->param_value;
        }
        if (is_numeric($default)) {
            $paramValue = intval($paramValue);
        }
        return $paramValue;
    }
}