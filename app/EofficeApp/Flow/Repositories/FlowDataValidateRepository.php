<?php

namespace App\EofficeApp\Flow\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Flow\Entities\FlowDataValidateEntity;

class FlowDataValidateRepository extends BaseRepository {

    public function __construct(FlowDataValidateEntity $entity) {
        parent::__construct($entity);
        $this->langService = 'App\EofficeApp\Lang\Services\LangService';
    }
    //获得验证数据
    public function getFlowValidateData($node_id,$lang=false){
    	$data = $this->entity->where('node_id',$node_id)->get()->toArray();
    	if($lang){
    		//获取多语言提示文字
    		foreach($data as $key => $value){
                $data[$key]['prompt_text'] = trans_dynamic("flow_data_validate.prompt_text.prompt_text_" . $value['id']);
    			$data[$key]['prompt_text_lang'] = app($this->langService)->transEffectLangs("flow_data_validate.prompt_text.prompt_text_" . $value['id']);
    		}
    	}
    	return $data;
    }
    //保存验证数据
    public function flowValidateSaveData($data,$nodeId){
    	if (!empty($data['validate']) && is_array($data['validate'])) {
    		$this->deleteByWhere(["node_id" => [$nodeId]]);
    		//循环处理验证数据
    		foreach ($data['validate'] as $key => $value) {
                $value['prompt_text'] = trim($value['prompt_text']);
                // 文件验证模式时可以不设置提示文字，从文件返回提示文字
                if ($value['prompt_text'] === '' && $value['validate_type'] == 0) {
                    return ['code' => ['the_prompt_cannot_be_empty', 'flow'], 'dynamic' => trans('flow.the_prompt_cannot_be_empty', ['data_validate_key' => ($key + 1)])];
                }
    			//条件验证
    			if ($value['validate_type'] == 0) {
    				$_insertData = [
    						'node_id' => $nodeId,
    						'conditions_value' => $value['conditions_value'],
    						'validate_type' => $value['validate_type'],
    						'prompt_text'=>$value['prompt_text']
    				];
    				$model = $this->insertData($_insertData);
    			}else{
    				//文件验证
    				$_insertData = [
    						'node_id' => $nodeId,
    						'file_url' => $value['file_url'],
    						'validate_type' => $value['validate_type'],
    						'prompt_text'=>$value['prompt_text']
    				];
    				$model = $this->insertData($_insertData);
    			}
    			//添加多语言提示文字
    			if(!empty($value['prompt_text_lang'])){
    				//循环处理多语言提示文字
    				foreach($value['prompt_text_lang'] as $k =>$v){
    					$langData = [
    							'table'      => 'flow_data_validate',
    							'column'     => 'prompt_text',
    							'lang_key'   => "prompt_text_" . $model->id,
    							'lang_value' => $v,
    					];
    					$local = $k;
    					app($this->langService)->addDynamicLang($langData, $local);
    				}
    			}
    		}
    	}
    }
}