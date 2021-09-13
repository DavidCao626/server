<?php
namespace App\EofficeApp\LogCenter\Builders;
/**
 * Description of LogConfigBuilder
 *
 * 选项构建器
 * 
 * @author lizhijun
 */
class OptionsBuilder 
{
    private $options = [];
    
    public function setOption($key, $value)
    {
        $this->options[$key] = $value;
    }
    
    public function getOptions()
    {
        return $this->options;
    }
}
