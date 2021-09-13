<?php


namespace App\Exceptions;


class ErrorMessage extends \Exception
{
    protected $dynamic;

    protected $module;

    public function getCodeArray()
    {
        if(isset($this->module)){
            $code = [$this->message, $this->module];
        }else{
            $content = explode('.', $this->message);
            $code = [$content[1], $content[0]];
        }
        if(isset($this->dynamic)){
            array_push($code, $this->dynamic);
        }

        return $code;
    }

    public function getErrorMessage()
    {
        if($this->dynamic){
            return $this->dynamic;
        }

        $codeArray = $this->getCodeArray();

        return trans($codeArray[1] . '.' . $codeArray[0]);
    }

    public function setDynamic($dynamic)
    {
        $this->dynamic = $dynamic;

        return $this;
    }

    public function getDynamic($dynamic)
    {
        return $this->dynamic;
    }
}
