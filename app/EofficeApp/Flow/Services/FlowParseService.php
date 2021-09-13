<?php
namespace App\EofficeApp\Flow\Services;

use App\EofficeApp\Flow\Entities\FlowFormControlStructureEntity;
use Cache;
use GuzzleHttp\Client;
use App\EofficeApp\Flow\Services\FlowBaseService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Redis;

class FlowParseService extends FlowBaseService
{

    public function __construct(
    )
    {
        parent::__construct();
    }

    //获取归档文件夹解析规则
    function getFlowFilingFolderRule($data)
    {
        $flowId   = isset($data["flow_id"]) ? $data["flow_id"] : "";
        $userId   = isset($data["creator"]) ? $data["creator"] : "";
        $userName = isset($data["user_name"]) ? $data["user_name"] : "";
        $formData = isset($data["form_data"]) ? $data["form_data"] : "";
        $formStructure = isset($data["form_structure"]) ? $data["form_structure"] : "";
        // 关联获取定义流程的所有数据
        $flowTypeAllObject = app($this->flowTypeRepository)->getFlowTypeInfoRepository($data, ['flow_form_type']);
        if(isset($flowTypeAllObject)) {
            $flowTypeAllInfo = $flowTypeAllObject->toArray();
        } else {
            return [];
        }
        $rule = [];
        $currentTime = date('Y-m-d H:i:s', time());
        if (isset($data['flow_filing_folder_rules']) && !empty($data['flow_filing_folder_rules'])) {
            $ruleInfo = json_decode($data['flow_filing_folder_rules'], true);
            if (!empty($ruleInfo) && is_array($ruleInfo)) {
            	$flowSortObject = app($this->flowSortRepository)->getDetail($flowTypeAllInfo['flow_sort']);
				if($flowSortObject){
					$flowSortInfo = $flowSortObject->toArray();
				}
                foreach ($ruleInfo as $key => $value) {
                    $type = $value['type'];
                    switch ($type) {
                        // 自增的自增重置依据的（暂不支持）
                        case 'increase':

                            break;
                        // 日期时间
                        case 'date':
                            $rule[] = date($value['attribute']['format'], time());
                            break;
                        // 流程信息
                        case 'flowInfo':
                            if (isset($value['control_id'])) {
                                $controlId = $value['control_id'];
                                switch ($controlId) {
                                    // 定义流程名称
                                    case 'flowDefineName':
                                        $rule[] = $flowTypeAllInfo['flow_name'];
                                        break;
                                    // 定义流程ID
                                    case 'flowDefineId':
                                        $rule[] = $flowTypeAllInfo['flow_id'];
                                        break;
                                    // 表单名称
                                    case 'formName':
                                        $rule[] = $flowTypeAllInfo['flow_type_has_one_flow_form_type']['form_name'];
                                        break;
                                    // 表单ID
                                    case 'formId':
                                        $rule[] = $flowTypeAllInfo['form_id'];
                                        break;
                                    // 流程分类名称
                                    case 'flowSortName':
                                        $rule[] = isset($flowSortInfo['title'])?$flowSortInfo['title']:"";
                                        break;
                                    // 流程分类名称
                                    case 'flowSortName':
                                        $rule[] = isset($flowSortInfo['title'])?$flowSortInfo['title']:"";
                                    	break;
                                    // 流程创建人名称
                                    case 'flowCreator':
                                        $rule[] = !empty($userId) ? app($this->userService)->getUserName($userId) : $userName;
                                        break;
                                    // 流程创建时间
                                    case 'flowCreateTime':
                                        $rule[] = $currentTime;
                                        break;
                                    // 流程运行ID
                                    case 'flowRunId':

                                        break;
                                    default:

                                        break;
                                }
                            }
                            break;
                        case 'formData':
                            if (isset($value['control_id'])) {
                                $controlId = $value['control_id'];
                                $formControlType = isset($formStructure[$controlId]['control_type']) ? $formStructure[$controlId]['control_type'] : '';
                                $formControlTitle = isset($formStructure[$controlId]['control_title']) ? $formStructure[$controlId]['control_title'] : '';
                                if (isset($data['run_name_preview']) && $data['run_name_preview']) {
                                    $rule[] = $formControlTitle;
                                } else {
                                    if (!empty($formControlType)) {
                                        // 排除复选框、签名图片、明细、附件、会签
                                        if (isset($formData[$controlId.'_TEXT']) && !empty($formData[$controlId.'_TEXT'])) {
                                            if (is_array($formData[$controlId.'_TEXT'])) {
                                                $formData[$controlId.'_TEXT'] = implode(',', $formData[$controlId.'_TEXT']);
                                            }
                                            $rule[] = $formData[$controlId.'_TEXT'];
                                        } else {
                                            if (isset($formData[$controlId]) && !empty($formData[$controlId])) {
                                                if ($formControlType == 'text') {
                                                    $formControlAttribute = isset($formStructure[$controlId]['control_attribute']) && !empty($formStructure[$controlId]['control_attribute']) ? json_decode($formStructure[$controlId]['control_attribute'], true) : [];
                                                    if (isset($formControlAttribute['data-efb-amount-in-words']) && $formControlAttribute['data-efb-amount-in-words']) {
                                                        // 金额大写
                                                        $rule[] = app($this->flowRunService)->digitUppercase($formData[$controlId]);
                                                    } elseif (isset($formControlAttribute['data-efb-thousand-separator']) && $formControlAttribute['data-efb-thousand-separator']) {
                                                        // 千位分隔符
                                                        $parts = explode('.', $formData[$controlId], 2);
                                                        $int = isset($parts[0]) ? strval($parts[0]) : '0';
                                                        $dec = isset($parts[1]) ? strval($parts[1]) : '';
                                                        $dec_len = strlen($dec) > 8 ? 8 : strlen($dec);
                                                        $rule[] = number_format(floatval($formData[$controlId]), $dec_len, '.', ',');
                                                    } else {
                                                        $rule[] = $formData[$controlId];
                                                    }
                                                } else {
                                                    if(is_array($formData[$controlId])) {
                                                        $formData[$controlId] = implode(',',$formData[$controlId]);
                                                    }
                                                    $rule[] = $formData[$controlId];
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                            break;
                        case 'txt':
                            $rule[] = isset($value['title']) ? $value['title'] : '';
                            break;
                        default:

                            break;
                    }
                }
            }
        }

        return $rule;
    }

    //获得表单数据模板
    public function getFormDataTemplate($data,$own=null){
    	$flowId = $data['flowId'];
    	$list = isset($data['list'])?$data['list']:false;
    	$id = isset($data['id'])?$data['id']:0;
    	$name = isset($data['name'])?$data['name']:"";
    	$type = isset($data['type'])?$data['type']:null;
        $order = isset($data['order_by'])?$data['order_by']:null;
    	return app($this->flowFormDataTemplateRepository)->getDataTemplate($flowId,$list,$id,$name,$type,$own , null , $order);
    }

    //设置表单数据模板
    public function setFormDataTemplate($data, $own = null){
    	return app($this->flowFormDataTemplateRepository)->setDataTemplate($data, $own);
    }

    //删除表单数据模板
    public function deleteFormDataTemplate($formId,$flow_id=0){
    	$flowList = app($this->flowTypeRepository)->getFlowListForFormId($formId);
    	if($flowList){
    		$flowList = $flowList->toArray();
    		foreach($flowList as $flowInfo){
    			if($flow_id&&$flowInfo['flow_id']!=$flow_id){
   					continue;
    			}
				app($this->flowOthersRepository)->updateData(['flow_show_data_template'=>'0'],['flow_id'=>[$flowInfo['flow_id']]]);
				app($this->flowFormDataTemplateRepository)->deleteByWhere(['flow_id'=>[$flowInfo['flow_id']]]);
    		}
    	}
    }

    //复制流程数据模板
    public function copyFormDataTemplate($flowId,$copyFlowId, $own){
    	$data = $this->getFormDataTemplate(['flowId'=>$flowId,'list'=>true,'type'=>0], $own);
    	if($data){
    		foreach($data as $v){
    			$item = [];
    			$item['flow_id'] = $copyFlowId;
    			$item['data_template'] = json_encode($v['data_template']);
    			$item['name'] = $v['name'];
    			app($this->flowFormDataTemplateRepository)->insertData($item);
    		}
    	}
    }
    //复制数据验证
    public function copyFormValidate($nodeId,$copyNodeId,$processInfo){
    	if(!empty($processInfo['flow_data_valid_toggle'])){
    		$data = [];
    		$nodeInfo = $this->getFlowValidateData(['node_id'=>$nodeId, 'lang' => true]);
    		foreach($nodeInfo as $k => $v){
    			$data['validate'][$k]['conditions_value'] = $v['conditions_value'];
    			$data['validate'][$k]['validate_type'] = $v['validate_type'];
    			$data['validate'][$k]['prompt_text'] = $v['prompt_text'];
                $data['validate'][$k]['prompt_text_lang'] = $v['prompt_text_lang'] ?? [];
    			$data['validate'][$k]['file_url'] = $v['file_url'];
    		}
            $flowDataValidMode = $processInfo['flow_data_valid_mode'] ?? 0;
    		app($this->flowProcessRepository)->updateData(['flow_data_valid_toggle' => 1, 'flow_data_valid_mode' => $flowDataValidMode], ['node_id' => [$copyNodeId]]);
            // 清空节点信息redis缓存
            if(Redis::exists('flow_process_info_'.$copyNodeId)) {
                Redis::del('flow_process_info_'.$copyNodeId);
            }
    		app($this->flowDataValidateRepository)->flowValidateSaveData($data,$copyNodeId);
    	}
    }
    //流程数据验证保存数据
    public function flowValidateSaveData($data, $userInfo, $saveType=""){
        if(isset($data["node_id"]) && $data["node_id"] == "batchNode") {
            $batchNode = isset($data["batchNode"]) ? $data["batchNode"] : [];
            if(empty($batchNode)) {
                // 保存失败，未获取到流程节点ID
                return ['code' => ['0x030155', 'flow']];
            } else {
                unset($data["batchNode"]);
                $saveResult = "";
                foreach ($batchNode as $key => $nodeId) {
                    $data["node_id"] = $nodeId;
                    $saveResult = $this->flowValidateSaveData($data, $userInfo, "batchNode");
                    if (isset($saveResult['code'])) {
                        return $saveResult;
                    }
                }
                return $saveResult;
            }
        }
    	if (isset($data['node_id']) && $data['node_id']) {
    		$nodeId = $data['node_id'];
            // 取node详情，获取里面的validate list和toggle
            $historyNodeInfo = app($this->flowService)->getFlowNodeInfo($nodeId);
    		if(isset($data['flow_data_valid_toggle'])) {
    			if ($data['flow_data_valid_toggle'] == 1) {
    				$saveResult = app($this->flowDataValidateRepository)->flowValidateSaveData($data,$nodeId);
                    if (isset($saveResult['code'])) {
                        return $saveResult;
                    }
                    $flowProcessUpdateData = [
                        'flow_data_valid_toggle' => 1,
                        'flow_data_valid_mode' => $data['flow_data_valid_mode'] ?? 0
                    ];
    				app($this->flowProcessRepository)->updateData($flowProcessUpdateData, ['node_id' => [$nodeId]]);
    			}else{
    				app($this->flowProcessRepository)->updateData(['flow_data_valid_toggle' => 0, 'flow_data_valid_mode' => 0], ['node_id' => [$nodeId]]);
    			}
                // 清空节点信息redis缓存
                if(Redis::exists('flow_process_info_'.$nodeId)) {
                    Redis::del('flow_process_info_'.$nodeId);
                }
    			Cache::delete('flow_process_detail'.$nodeId);
                // 拼装日志参数
                $historyData                           = [];
                $historyData["flow_data_valid_toggle"] = isset($historyNodeInfo["flow_data_valid_toggle"]) ? $historyNodeInfo["flow_data_valid_toggle"] : "0";
                $historyData["validate"]               = isset($historyNodeInfo["validate"]) ? $historyNodeInfo["validate"] : [];
                $newData                               = [];
                $newNodeInfo                           = app($this->flowService)->getFlowNodeInfo($nodeId);
                $newData["flow_data_valid_toggle"]     = isset($newNodeInfo["flow_data_valid_toggle"]) ? $newNodeInfo["flow_data_valid_toggle"] : "0";
                $newData["validate"]                   = isset($newNodeInfo["validate"]) ? $newNodeInfo["validate"] : [];
                $routFrom                              = isset($data['route_from']) ? $data['route_from'] : '';
                // 调用日志函数
                $logParam = [];
                $logParam["new_info"] = json_decode(json_encode($newData),true);
                $logParam["history_info"] = json_decode(json_encode($historyData),true);
                app($this->flowLogService)->logFlowDefinedModify($logParam,"flow_process&node_id",$nodeId,$routFrom,$userInfo,$saveType);
    		}
    	}
    	return true;
    }

    //获取流程验证数据
    public function getFlowValidateData($data){
    	if(isset($data['detail'])){
    		if(Cache::has('flow_process_detail'.$data['node_id'])){
    			return Cache::get('flow_process_detail'.$data['node_id']);
    		}
    		$validateData = app($this->flowProcessRepository)->entity->select(['flow_data_valid_toggle','flow_data_valid_mode'])->where(['node_id'=>$data['node_id']])->first();
    		Cache::forever('flow_process_detail'.$data['node_id'],$validateData);
    		return $validateData;
    	}
    	$lang = isset($data['lang'])?$data['lang']:false;
    	$validateData = app($this->flowDataValidateRepository)->getFlowValidateData($data['node_id'],$lang);
		return $validateData;
    }

	//验证流程数据
	public function validateFlowData($data,$own){
        if(isset($data["flowFormData"])) {
            $flowFormData = $data["flowFormData"];
        } else {
            // 从路由 flow/run/data-validate 过来，只有 data ，没有 Structure ，需要解析&包装，在validateFlowDataAchieve里会重新获取结构
            $flowFormData = [];
    		if($data['formData']){
    			$flowFormData['route'] = true;
    			if(!is_array($data['formData'])){
    				$flowFormData['parseData'] = json_decode($data['formData'],true);
    			}else{
    				$flowFormData['parseData'] = $data['formData'];
    			}
    		}
        }
		$process = app($this->flowRunStepRepository)->entity->where('run_id',$data['run_id'])->where('user_id',$own['user_id'])->where('flow_process',$data['node_id'])->first();
        $processId = $process->process_id ?? 0;
		if(isset($process->host_flag)&&$process->host_flag==0){
			$opFlagIsExistResult = app($this->flowRunService)->opFlagIsExist(["run_id" => $data['run_id'], "process_id" =>$process->process_id]);
			if($opFlagIsExistResult==0){
				$handleWay = app($this->flowProcessRepository)->findFlowNode($data['node_id'],'process_transact_type');
				//没有主办人且办理方式是第一或第三
				if ($handleWay == "0"||$handleWay == "2") {
					return ['validate'=>true];
				}
				if ($handleWay == "3") {
					$process_concourse = app($this->flowProcessRepository)->findFlowNode($data['node_id'],'process_concourse');
				}
			}else{
				return ['validate'=>true];
			}
		}
		return $this->validateFlowDataAchieve($own,$data['flow_id'],$data['form_id'],$data['node_id'],$processId,$data['run_id'],$flowFormData);
    }

    //验证流程数据实现
    public function validateFlowDataAchieve($own,$flow_id,$form_id=0,$node_id=0,$processId=0,$run_id=0,$flowFormData=[]){
    	//验证结果
    	$validate['validate'] = true;
    	//验证提示文字
    	$validate['prompt_text'] = '';
    	$process = $this->getFlowValidateData(['node_id'=>$node_id,'detail'=>$flow_id]);
        $flowDataValidMode = $process['flow_data_valid_mode'] ?? 0;
    	if(isset($process['flow_data_valid_toggle']) && !$process['flow_data_valid_toggle']){
    		return $validate;
    	}
        $flowTypeInfo = app($this->flowTypeRepository)->getDetail($flow_id, false, ['flow_type']);
    	if(!empty($flowTypeInfo->flow_type) && $flowTypeInfo->flow_type=="2"){
    		return $validate;
    	}
    	$validateData = [];
        $validateResult = [];
    	if($node_id){
    		//获取验证规则
            $validateData = $this->getFlowValidateData(['node_id'=>$node_id]);
    		if($validateData){
    			//条件验证参数
    			$flowOtherInfo['process_id'] = $processId;
    			$flowOtherInfo['user_id'] = $own['user_id'];
                if(!empty($flowFormData) && isset($flowFormData['parseData']) && isset($flowFormData['parseFormStructure'])) {
                    // 20181213-dp修改，如果外部传了 getFlowFormParseData 函数的返回值，就不需要再查询了
                    $parseData = $flowFormData['parseData'];
                    $parseFormStructure = $flowFormData['parseFormStructure'];
                } else {
                    $flowFormDataParam = ['status' => 'handle','runId' => $run_id,'formId' => $form_id,'flowId' => $flow_id,'nodeId' => $node_id];
                    //获取表单数据
                    $flowFormParseData = app($this->flowService)->getFlowFormParseData($flowFormDataParam,$own);
                    if(isset($flowFormData['parseData'])) {
                        $parseData = $flowFormData['parseData'];
                    } else {
                        $parseData = $flowFormParseData['parseData'];
                    }
                    $parseFormStructure = $flowFormParseData['parseFormStructure'];
                }
                $formData = $parseData;
                //表单结构
                $flowOtherInfo['form_structure'] = $parseFormStructure;
    			if(isset($flowFormData['route'])){
    				$formData =  $flowFormData['parseData'];
    			}
                $originalFormData = $formData;
                // 处理表单数据
                foreach($flowOtherInfo['form_structure'] as $k => $v){
                    $item = json_decode($v['control_attribute'], true);
                    if ((isset($item['data-efb-format']) && in_array($item['data-efb-format'], ['date','time','datetime']))){
                        if (isset($formData[$k])&&!is_array($formData[$k])) {
                            if ($formData[$k] !== '') {
                                $formData[$k] = strtotime($formData[$k]);
                                if(!$formData[$k]) $formData[$k] = "";
                            } else {
                                $formData[$k] = "";
                            }
                        }
                    } else {
                        if (isset($formData[$k])) {
                            if (!is_array($formData[$k])) {
                                if ($v['control_type'] == "countersign") {
                                    if (isset($formData[$k."_COUNTERSIGN"])) {
                                        $formData[$k] = $formData[$k."_COUNTERSIGN"]['countersign_content'] ?? '';
                                    }
                                }
                                if (isset($formData[$k."_TEXT"])) {
                                    if ($formData[$k] !== '') {
                                        if (!is_array($formData[$k."_TEXT"])) {
                                            $formData[$k] = $formData[$k."_TEXT"];
                                        } else {
                                            $formData[$k] = implode(",",$formData[$k."_TEXT"]);
                                        }
                                    }
                                }
                            } else {
                                if ($v['control_type'] != "countersign") {
                                    if (!isset($formData[$k][0])) {
                                        $formData[$k] = implode(",",$formData[$k]);
                                    }
                                }
                                if (isset($formData[$k."_TEXT"])) {
                                    if ($formData[$k] !== ''){
                                        if (!is_array($formData[$k."_TEXT"])) {
                                            $formData[$k] = $formData[$k."_TEXT"];
                                        } else {
                                            $formData[$k] = implode(",",$formData[$k."_TEXT"]);
                                        }
                                    } else {
                                        $formData[$k] = "";
                                    }
                                    unset($formData[$k."_TEXT"]);
                                }
                            }
                        }
                    }
                }
                $validateCount = count($validateData);
                for ($i=0; $i < $validateCount; $i++) {
                    $value = $validateData[$i] ?? '';
                    if ($value === '') {
                        continue;
                    }
    				//条件验证
    				if($value['validate_type']==0){
    					//调用验证函数
    					try{
    						$newFlow = isset($flowFormData['route'])?true:false;
    						$verifyResult = app($this->flowRunService)->verifyFlowFormOutletCondition($value['conditions_value'], $formData, $flowOtherInfo,true,$newFlow);
    						if(!$verifyResult){
    							$validate['validate'] = false;
    							$validate['prompt_text'] = $this->getDataValidatePromptText($value);
                                $validate['flow_data_valid_mode'] = $flowDataValidMode;
                                if ($flowDataValidMode == '1') {
                                    $validateResult[] = $validate;
                                } else {
                                    return $validate;
                                }
    						} else {
                                if ($i < ($validateCount - 1)) {
                                    continue;
                                }
                                if ($flowDataValidMode != '1') {
                                    return $validate;
                                }
    						}
    					}catch(\Exception $e){
    						$validate['validate'] = false;
    						$validate['prompt_text'] = $this->getDataValidatePromptText($value);
                            $validate['flow_data_valid_mode'] = $flowDataValidMode;
    						$validate['exception'] = $e->getMessage();
                            if ($flowDataValidMode == '1') {
                                $validateResult[] = $validate;
                            } else {
                                return $validate;
                            }
    					}catch(\Error $error) {
    						$validate['validate'] = false;
    						$validate['prompt_text'] = $this->getDataValidatePromptText($value);
                            $validate['flow_data_valid_mode'] = $flowDataValidMode;
    						$validate['error'] = "";
    						if ($flowDataValidMode == '1') {
                                $validateResult[] = $validate;
                            } else {
                                return $validate;
                            }
    					}
    				//文件验证
    				}else{
    					try {
    						$formData['run_id'] = $run_id;
    						$formData['flow_id'] = $flow_id;
    						$formData['form_id'] = $form_id;
                            $originalFormData['run_id'] = $run_id;
                            $originalFormData['flow_id'] = $flow_id;
                            $originalFormData['form_id'] = $form_id;
                            $formData['original_data'] = $originalFormData;
                            $value['file_url'] = parse_relative_path_url($value['file_url']);
    						$guzzleResponse = (new Client())->request('POST',$value['file_url'], ['form_params' => $formData]);
    						$status = $guzzleResponse->getStatusCode();

    					} catch (\Exception $e) {
    						$status = $e->getMessage();
                            $validate = [
                                'validate' => false,
                                'prompt_text' => $status,
                                'content' => $status,
                                'flow_data_valid_mode' => $flowDataValidMode
                            ];
                            if ($flowDataValidMode == '1') {
                                return [$validate];
                            } else {
                                return $validate;
                            }
    					}
    					$validateFlag = true;
    					$content = '';
						if(!empty($guzzleResponse)){
							//返回结果
                            $content = $guzzleResponse->getBody()->getContents();
							if($content=='true'||$content=='1'){
								$validateFlag = false;
							}
						}
						if($validateFlag){
							$validate['validate'] = false;
                            $promptText = $this->getDataValidatePromptText($value);
                            if ($promptText === '') {
                                $promptText = $content;
                            }
							$validate['prompt_text'] = $promptText;
							$validate['content'] = $content;
                            $validate['flow_data_valid_mode'] = $flowDataValidMode;
							if ($flowDataValidMode == '1') {
                                $validateResult[] = $validate;
                            } else {
                                return $validate;
                            }
						} else {
                            if ($i < ($validateCount - 1)) {
                                continue;
                            }
                            if ($flowDataValidMode != '1') {
                                return $validate;
                            }
						}
    				}
    			}
    		}
    	}
        if ($flowDataValidMode == '1') {
            if (empty($validateResult)) {
                return $validate;
            } else {
                return $validateResult;
            }
        } else {
            return $validate;
        }
    }

    //获取数据验证提示文字
    public function getDataValidatePromptText($value){
		$prompt_text = $value['prompt_text'];
		$prompt_text_lang = app($this->langService)->transEffectLangs("flow_data_validate.prompt_text.prompt_text_" . $value['id']);
		if(!empty($prompt_text_lang)){
			$local = Lang::getLocale();
			if(!empty($prompt_text_lang[$local])){
				$prompt_text = $prompt_text_lang[$local];
			}
		}
		return $prompt_text;
    }

    //流程挂起
    public function hangupFlow($param){
        if (empty($param['user_id'])) {
            return false;
        }
    	$data = ['hangup'=>1,'cancel_hangup_time'=>'0000-00-00 00:00:00'];
    	if(!empty($param['cancel_hangup_time'])){
    		$data['cancel_hangup_time'] = $param['cancel_hangup_time'];
    	}
        $flowRunDetail = app($this->flowRunProcessRepository)->getDetail($param['flow_step_id'], false, ['run_id', 'flow_id', 'process_id','host_flag','flow_process']);
    	$result = app($this->flowRunStepRepository)->updateData($data,['flow_step_id' => [$param['flow_step_id']], 'user_id' => [$param['user_id']]]);
    	app($this->flowRunProcessRepository)->updateData($data,['flow_run_process_id' => [$param['flow_step_id']], 'user_id' => [$param['user_id']]]);
        if($result) {
            $todu_push_params = [];
            $todu_push_params['receiveUser'] = $param['user_id'];
            $todu_push_params['deliverTime'] = '';
            $todu_push_params['deliverUser'] = $param['user_id'];
            $todu_push_params['operationType'] = 'reduce';
            $todu_push_params['operationId'] = '7';
            $todu_push_params['flowId'] = $flowRunDetail->flow_id ?? '';
            $todu_push_params['runId'] = $flowRunDetail->run_id ?? '';
            $todu_push_params['processId'] = $flowRunDetail->process_id ?? '';
            $todu_push_params['flowRunProcessId'] = $param['flow_step_id'];
            // 操作推送至集成中心
            app($this->flowLogService)->addOperationRecordToIntegrationCenter($todu_push_params);
        }
        return $result;
    }

    //取消流程挂起
    public function cancelHangupFlow($param){
        if (empty($param['user_id'])) {
            return false;
        }
    	return app($this->flowRunProcessRepository)->updateData(['hangup' => 0,'cancel_hangup_time'=>'0000-00-00 00:00:00'], ['flow_run_process_id' => [$param['flow_step_id']]]);
    }

    // 排序
    public function sortByFiled($data, $filed1, $type1, $filed2, $type2,$filed3 = '', $type3 = '',$filed5 = '', $type5 = '',$filed6 = '', $type6 = '')
    {
    	if (count($data) <= 0) {
    		return $data;
    	}
    	$len = 100000;
    	$index = 100000;
    	$serial = 0;
    	foreach ($data as $key => $value) {
    		$temp_array1[$key] = isset($value[$filed1])?$value[$filed1]:0;
    		if(empty($value[$filed2])){
    			$len++;
    		}
    		$temp_array2[$key] = $value[$filed2];
    		if(!empty($filed3)){
    			if(!isset($value[$filed3])){
    				$value[$filed3] = 0;
    				$index++;
    			}
    			$temp_array3[$key] = $value[$filed3];
    		}
    		if(!empty($filed5)){
    			if(!isset($value[$filed5])){
    				$value[$filed5] = $serial;
    				$serial++;
    			}
    			$temp_array5[$key] = $value[$filed5];
    		}
    		if(!empty($filed6)){
    			if(!isset($value[$filed6])){
    				$value[$filed6] = $serial;
    				$serial++;
    			}
    			$temp_array6[$key] = $value[$filed6];
    		}
    	}
    	if (!empty($filed6)) {
    		array_multisort($temp_array1, $type1, $temp_array2, $type2,$temp_array3, $type3,$temp_array5,$type5,$temp_array6,$type6, $data);
    	} else {
    		array_multisort($temp_array1, $type1, $temp_array2, $type2,$temp_array3, $type3,$temp_array5,$type5, $data);
    	}
    	return $data;
    }

    //保存用户模板
    public function saveUserTemplate($param,$userInfo=[]){
    	return app($this->flowFormDataTemplateRepository)->saveUserTemplate($param,$userInfo);
    }
    //删除用户模板
    public function deleteUserTemplate($id,$userInfo=[]){
        $detail = app($this->flowFormDataTemplateRepository)->getDetail($id);

        //验证新建流程权限
        if(!$detail->flow_id || !app($this->flowPermissionService)->verifyFlowNewPermission(['own'=>$userInfo,'flow_id'=>$detail->flow_id])) {
            return $this->returnResult(['code' => ['0x000006', 'common']]);
        }
        if(isset($userInfo['user_id']) && $detail && $detail->user_id && $detail->user_id==$userInfo['user_id']) {
            return app($this->flowFormDataTemplateRepository)->deleteUserTemplate($id);
        }
    }

    // 获取节点信息
    public function getProcessInfo($processId,$cache = true){
    	if(empty($processId)){
    		return [];
    	}
    	$data = [];
    	static $processInfo;
    	if(isset($processInfo[$processId]) && $cache){
    		return $processInfo[$processId];
    	}else{
            $dbData = app($this->flowProcessRepository)->getOneFieldInfo(['node_id'=>$processId]);
    		if(!empty($dbData)){
    			$data = $dbData->toArray();
    			$processInfo[$processId] = $data;
    			return $data;
    		}
    	}
    	return $data;
    }

    // 获取运行节点信息
    public function getFlowRunProcessInfo($runId){
    	if(empty($runId)){
    		return [];
    	}
    	$data = [];
    	static $flowProcessInfo;
    	if(isset($flowProcessInfo[$runId])){
    		return $flowProcessInfo[$runId];
    	}else{
    		$dbData = app($this->flowRunProcessRepository)->entity->select(['flow_run_process_id','run_id','process_id','user_id','deliver_time','saveform_time','receive_time','process_time','process_flag','flow_process','process_type','host_flag','sub_flow_run_ids','monitor_submit','is_effect','flow_id','created_at','updated_at','is_back','send_back_process','send_back_user','concurrent_node_id','origin_process','origin_user','outflow_process','outflow_user','is_back','send_back_process','send_back_user','origin_process_id','flow_serial','branch_serial','process_serial'])->where(['run_id'=>$runId])->get()->toArray();
    		if(!empty($dbData[0])){
    			$data =  $dbData;
    			$flowProcessInfo[$runId] = $data;
    			return $data;
    		}
    	}
    	return $data;
    }

    // 获取流程节点
    public function getFlowProcessInfo($flowId,$cache = true){
    	if(empty($flowId)){
    		return [];
    	}
    	$data = [];
    	static $flowInfo;
    	if(isset($flowInfo[$flowId]) && $cache){
    		return $flowInfo[$flowId];
    	}else{
    		$dbData = app($this->flowProcessRepository)->entity->select('node_id','flow_id','process_id','sort','process_name','process_type','process_to','end_workflow','head_node_toggle','concurrent','merge' , 'branch' , 'merge_node' , 'origin_node' , 'head_node_toggle' , 'line_to_merge_node')->where(['flow_id'=>$flowId])->orderBy('sort', 'ASC')->get()->toArray();
    		if(!empty($dbData[0])){
    			$data =  $dbData;
    			$flowInfo[$flowId] = $data;
    			return $data;
    		}
    	}
    	return $data;
    }

    //获取归档文件夹解析规则
    public function isProcessSubmit($runId,$flowProcess,$processId){
    	$submitResult =  false;
    	$dbData = app($this->flowRunProcessRepository)->entity->select(['flow_process'])->where(['run_id'=>$runId,'flow_process'=>$flowProcess,'process_id'=>$processId,'host_flag'=>1])->whereRaw("((deliver_time != '0000-00-00 00:00:00') and (deliver_time IS NOT NULL))")->get()->toArray();
    	if(!empty($dbData)){
    		$submitResult = true;
    	}
    	return $submitResult;
    }

    // 是否是并发流程
    public function isConcurrentFlow($flowId){
        return app($this->flowProcessRepository)->getFlowProcessList([
            'flow_id' => $flowId,
            'search' => [
                'concurrent' => [0, '!=']
            ],
            'returntype' => 'count'
        ]);
    }

    // 流程节点是否提交
    public function isFlowProcessSubmit($runId,$flowProcess,$originProcess=null){
    	$submitResult =  false;
    	$dbData = app($this->flowRunProcessRepository)->entity->select(['flow_process','process_id','outflow_process'])->where(['run_id'=>$runId,'host_flag'=>1,'flow_process'=>$flowProcess,'is_back'=>0])->whereRaw("((deliver_time != '0000-00-00 00:00:00') and (deliver_time IS NOT NULL))")->get()->toArray();
    	if(!empty($dbData)){
    		if(empty($originProcess)) {
    			$submitResult = true;
    		} else {
    			$dbDataInfo = app($this->flowRunProcessRepository)->entity->select(['flow_process','process_id','outflow_process'])->where(['run_id'=>$runId,'host_flag'=>1,'flow_process'=>$flowProcess,'is_back'=>0])->whereRaw("(deliver_time IS NULL)")->get()->toArray();
    			if (empty($dbDataInfo)) {
    				$submitResult = true;
    			}
    			$submit = false;
    			foreach($dbData as $v){
    				if (in_array($originProcess,explode(',',$v['outflow_process']))) {
    					$submit = true;
    					break;
    				}
    			}
    			if (!$submit) {
    				$submitResult = false;
    			}
    		}
    	}
    	return $submitResult;
    }

    // 是否是流程并发
    public function isFlowConcurrent($runId){
    	$flowProcessInfo = $this->getFlowRunProcessInfo($runId);
    	$result = false;
    	if($flowProcessInfo){
    		foreach($flowProcessInfo as $v){
    			if(!empty($v['concurrent_node_id'])){
    				$result = true;
    			}
    		}
    	}
    	return $result;
    }
    // 是否收到流程
    public function isFlowProcessReceive($runId,$flowProcess){
    	$receiveResult =  false;
    	$dbData = app($this->flowRunProcessRepository)->entity->where(['run_id'=>$runId,'flow_process'=>$flowProcess,'is_back'=>0])->count();
    	if(!empty($dbData)){
    		$receiveResult = true;
    	}
    	return $receiveResult;
    }

    // 获取运行节点
    public function getProcessFlowRun($runId,$flowProcess,$processId){
    	if(empty($runId)||empty($flowProcess)){
    		return [];
    	}
    	$data = [];
    	$index = $runId.$flowProcess;
    	static $processInfoFlowRun;
    	if(isset($processInfoFlowRun[$index])){
    		return $processInfoFlowRun[$index];
    	}else{
    		$dbData = app($this->flowRunProcessRepository)->entity->select('concurrent_node_id','branch_serial', 'flow_serial')->where(['run_id'=>$runId,'flow_process'=>$flowProcess,'process_id'=>$processId])->get()->toArray();
    		if(!empty($dbData[0])){
    			$data =  $dbData[0];
    			$processInfoFlowRun[$index] = $data;
    			return $data;
    		}
    	}
    	return $data;
    }

    /**
     *	流程提交
     *	param : 请求参数
     *  userInfo : 用户信息
     */
    public function postBranchTurning($param = [], $userInfo , $isAutoSubmit = false, $submitUuid = '')
    {
    	$flowProcess = isset($param['flow_process']) ? $param['flow_process'] : '';
    	$runId = isset($param['run_id'])?$param['run_id']: '';
    	$userId = isset($param['user_id'])?$param['user_id']: '';
		$flowTurnType = isset($param['flowTurnType'])?$param['flowTurnType']: '';
    	$processInfo = $this->getProcessInfo($flowProcess);
    	$param['concurrent'] = isset($processInfo['concurrent'])?$processInfo['concurrent']:0;
    	$param['merge'] = isset($processInfo['merge'])?$processInfo['merge']:0;
    	$param['process_info'] = $processInfo;
    	$monitor = isset($param['monitor']) ? $param['monitor'] : '';
    	$processId = isset($param["process_id"])?$param["process_id"]:'';
    	$runObject = app($this->flowRunRepository)->getDetail($runId , false , ['flow_id']);
    	$flowId = isset($runObject->flow_id) ? $runObject->flow_id : "";
    	$concurrentFlow = 0;
    	if (!empty($processInfo['flow_id'])) {
    		$concurrentFlow = $this->isConcurrentFlow($processInfo['flow_id']);
    	}
    	$param['concurrent_flow'] = $concurrentFlow;
    	$result = [];
    	$branchProcess = [];
		// 是否并发流程 0：不是并发流程，1：是并发流程
    	$parallelType = 0;
    	// 并发类型,0:不并发，1:非强制并发,2:强制并发
    	$branchType = 0;
    	// 合并类型,0:不合并，1:非强制合并,2:强制合并
    	// branch_type 用来跟普通流程作区分，标记是并发流程的提交,1:普通并发流程
    	$combineType = 0;
		if(!empty($processInfo['concurrent'])){
			$parallelType = $branchType = $processInfo['concurrent'];
		}
		if(!empty($processInfo['merge'])){
			$parallelType = $combineType = $processInfo['merge'];
		}
        if (isset($param['flow_run_process_id']) && $param['flow_run_process_id']) {
            // 验证是否为最新步骤,解决前段页面提交时，信息不是最新导致的权限问题
            $flowRunProcessInfo = app($this->flowRunProcessRepository)->getDetail($param['flow_run_process_id'], false, ['user_last_step_flag']);
            if(!$flowRunProcessInfo || !isset($flowRunProcessInfo->user_last_step_flag) || $flowRunProcessInfo->user_last_step_flag != 1) {
                return ['code' => ['0x030006', 'flow']];
            }
        }
    	$flowRunProcessInfo = app($this->flowRunProcessRepository)->getFlowRunProcessList(['search' => ['flow_process' => [$flowProcess], 'run_id' => [$runId], 'user_id' => [$userId],'host_flag'=>[1]],'order_by'=>['flow_run_process_id'=>'asc'],'fields'=>['is_back','deliver_time','concurrent_node_id','origin_process','send_back_user','send_back_process']]);
    	$flowRunProcessInfo = $flowRunProcessInfo->toArray();
    	$transactProcessInfo = !empty($param['transactProcessInfo'])?$param['transactProcessInfo']:[];
    	$transactProcess = !empty($transactProcessInfo['process'])?$transactProcessInfo['process']:[];
        $transactProcessTotal = !empty($transactProcessInfo['total']) ? $transactProcessInfo['total'] : 0;
        $processFlowRunInfo = $this->getProcessFlowRun($param['run_id'],$flowProcess,$processId);
        $param['branch_serial'] = $processFlowRunInfo['branch_serial'] ?? 0;
        $param['flow_serial'] = $processFlowRunInfo['flow_serial'] ?? 0;
        if(!empty($processFlowRunInfo['concurrent_node_id'])){
            $param['concurrent_node_id'] = $processFlowRunInfo['concurrent_node_id'];
        }
        // 如果是提交的或者退回重新提交的，并且当前节点不是并发节点的
    	if (empty($param['concurrent_node_id']) && ($flowTurnType == 'send_back_submit' || $flowTurnType == 'turn') && empty($transactProcess) && isset($processInfo['process_type']) && $processInfo['process_type'] == 'common') {
    	    if ($flowRunProcessInfo) {
    			$sendBackArray = [];
    			foreach($flowRunProcessInfo as $value){
    				if ($value['is_back'] == 1 && empty($value['deliver_time'])) {
    					$sendBackArray[] = $value;
    				}
    			}
    			if (count($sendBackArray) >1) {
    				foreach($sendBackArray as $key => $value){
    					$processTransactUser = [];
    					$flowRunTransactInfo = app($this->flowRunProcessRepository)->getFlowRunProcessList(['search' => ['flow_process' => [$value['origin_process']], 'run_id' => [$runId],'host_flag'=>[0]],'order_by'=>['flow_run_process_id'=>'asc'],'fields' => ['user_id','saveform_time','concurrent_node_id']]);
						if ($flowRunTransactInfo) {
							$flowRunTransactInfo = $flowRunTransactInfo->toArray();
							foreach($flowRunTransactInfo as $v){
								if (!in_array($v['user_id'],$processTransactUser)) {
									if (!empty($v['saveform_time'])) {
										$processTransactUser[] = $v['user_id'];
									}
								}
							}
						}
    					$param['process_host_user'] = $value['send_back_user'];
    					$param['process_transact_user'] = !empty($processTransactUser)?implode(',',$processTransactUser):'';
    					$param['next_flow_process'] = $value['send_back_process'];
    					$param['concurrent_node_id'] = $value['concurrent_node_id'];
    					$param['branch_type'] = 1;
    					$param['branch_process'] = 1;
    					$param['branch_complete'] = ($key == (count($sendBackArray)-1))?1:0;
    					$result = app($this->flowService)->postFlowTurning($param, $userInfo,$isAutoSubmit,$submitUuid);
    				}
    				return $result;
    			} else {
    				foreach($flowRunProcessInfo as $value){
    					if (!empty($value['concurrent_node_id'])){
    						$param['concurrent_node_id'] = $value['concurrent_node_id'];
    						break;
    					}
    				}
    			}
    		}
    	}
        // 如果是并发流程
    	if($parallelType){
            $transactProcessInfo = !empty($param['transactProcessInfo']['process'])?$param['transactProcessInfo']['process']:[];
            if (empty($transactProcessInfo[0]['hostUserInfo']) && empty($transactProcessInfo[0]['handleUserInfo'])) {
                $transactProcessInfo = [];
            }
            // 如果是合并节点
            if (!empty($combineType)) {
                if ($flowTurnType != 'back') {
                    // 合并节点提交
                    // 非强制合并
                    if ($combineType==1) {
                        return app($this->flowService)->postFlowTurning($param, $userInfo,$isAutoSubmit,$submitUuid);
                        // 强制合并
                    } else {
                        // 如果该强制合并节点不是由并发分支节点提交过来的，而是普通节点直接提交过来的，就不要再提示“流程处于汇总节点，尚有其他节点未提交至此”
                        if ($processFlowRunInfo['concurrent_node_id']) {
                            // 合并处理
                            if ($processInfo['merge'] == 2) {
                                $isForceMerge = $this->isFinishedMergeProcess($processInfo['flow_id'], $runId, $flowProcess);
                                if (!$isForceMerge) {
                                    return ['code' => ['0x030183', 'flow']];
                                }
                            }
                        }
                        // if (empty($transactProcessInfo)) {
                            return app($this->flowService)->postFlowTurning($param, $userInfo,$isAutoSubmit,$submitUuid);
                        // }
                    }
                } else {
                    // 合并节点退回
                    if (!empty($transactProcessInfo)) {
                        $branchCount = count($transactProcessInfo)-1;
                        $index = 0;
                        // 退回到多个节点
                        foreach ($transactProcessInfo as $transactProcessValue) {
                            if (empty($transactProcessValue['nodeId'])) {
                                continue;
                            }
                            // 查出可退回的节点的各自合并节点的process_id用于分别提交
                            $backRunProcessInfo = app($this->flowRunProcessRepository)->getFlowRunProcessList(['fields' => ['process_id','concurrent_node_id','process_type','free_process_step'], 'search' => ['run_id' => [$param["run_id"]], 'flow_process' => [$flowProcess], 'origin_process' => [$transactProcessValue['nodeId']],'host_flag' => [1]] , 'returntype' =>'first','order_by'=>['process_id'=>'desc'], 'whereRaw' => [" ( transact_time IS NULL OR transact_time = '0000-00-00 00:00:00') "]]);
                            $param['process_id'] = $backRunProcessInfo->process_id ?? $processId;
                            $processTransactUser = $transactProcessValue['handleUserInfo'] ?? '';
                            if (is_array($processTransactUser)) {
                                $processTransactUser = implode(",",$processTransactUser).",";
                            }
                            $param['process_host_user']     = $transactProcessValue['hostUserInfo'] ?? '';
                            $param['process_transact_user'] = $processTransactUser;
                            $param['next_flow_process']     = $transactProcessValue['nodeId'];
                            $param['concurrent_node_id']    = $backRunProcessInfo->concurrent_node_id ?? $flowProcess;
                            $param['branch_type']           = 1;
                            $param['branch_complete']       = ($branchCount==$index)?1:0;
                            $param['limit_date']            =  $transactProcessValue['limit_date'] ?? '';
                            $param['first_has_executed']    = $index >= 1 ? 1 : 0 ;
                            // 查目标节点信息
                            // 目标节点设置信息
                            $targetProcessSetInfo = app($this->flowProcessFreeRepository)->getFlowNodeDetail($transactProcessValue['nodeId']);
                            if ($targetProcessSetInfo) {
                                $param["free_process_next_step"] = 0;
                                $param["free_process"] = true;
                                $param["free_process_current_step"] = 0;
                                if($targetProcessSetInfo->back_to_type == 2) {
                                    // 退回到最后步骤时，按自由节点真实提交的最后步骤来退回，解决中途跳出自由节点的情况
                                    $searchParam = [
                                        'fields' => ['process_type','free_process_step'],
                                        'search' => [
                                            'run_id' => [$param["run_id"]],
                                            'flow_process' => [$transactProcessValue['nodeId']],
                                            'host_flag' => [1]
                                        ],
                                        'returntype' => 'first',
                                        'order_by' => ['process_id'=>'desc']
                                     ];
                                    $targetProcessInfo = app($this->flowRunProcessRepository)->getFlowRunProcessList($searchParam);
                                    $param["free_process_next_step"] = $targetProcessInfo->free_process_step;
                                }
                            }
                             // 该参数用于只执行一次部分操作，比如提交时选择多个节点抄送，循环会多次插入
                            $result = app($this->flowService)->postFlowTurning($param, $userInfo,$isAutoSubmit,$submitUuid);
                            $index++;
                        }
                    } else {
                        return app($this->flowService)->postFlowTurning($param, $userInfo,$isAutoSubmit,$submitUuid);
                    }
                }
            }
            // 如果是并发节点
            if(!empty($branchType)){
                if(empty($transactProcessInfo)){
                    return app($this->flowService)->postFlowTurning($param, $userInfo,$isAutoSubmit,$submitUuid);
                }
                $process = [];
                if(!empty($transactProcessInfo)) {
                    foreach($transactProcessInfo as $transactProcessValue){
                        $branchProcess[] = $transactProcessValue['nodeId'];
                        $process[$transactProcessValue['nodeId']] = $transactProcessValue;
                    }
                }
                // 如果是强制并发节点，且流出节点小于等于1
                if ($branchType == 2 && count($branchProcess) <= 1){
                    // 如果已经产生过分支数据了，说明已经并发过了，就不再检测出口数量了
                    $flowRunProcessDisposeCount = app($this->flowRunProcessRepository)->getFlowRunProcessList(['search' => ['run_id' => [$param["run_id"]], 'branch_serial' => [0, '>']] , 'returntype' =>'count']);
                    if (empty($flowRunProcessDisposeCount)) {
                        if (!($transactProcessTotal == '1' && count($branchProcess) == 1)) {
                            // 至少选择两个流出节点
                            return ['code' => ['please_choose_more_than_two_nodes', 'flow']];
                        }
                    }
                }
                // 如果流出节点不为空
                if(!empty($branchProcess)){
                    $branchCount = count($branchProcess)-1;
                    $index = 0;
                    // 获取并发节点是否是退回的步骤，如果是退回的要重新获取process_id来作为提交参数
                    $currentRunProcessInfo = app($this->flowRunProcessRepository)->getFlowRunProcessList(['fields' => ['is_back','flow_serial','branch_serial','process_id'],'search' => ['run_id' => [$param["run_id"]], 'flow_process' => [$param['flow_process']],'host_flag' => [1]] , 'returntype' =>'array', 'whereRaw' => [" ( transact_time IS NULL OR transact_time = '0000-00-00 00:00:00') "]]);
                    // 判断是否是退回的标识，如果有一条待办记录是分支线上的就算退回再提交的，就要查询每个分支并发节点所在位置用来作为提交参数
                    $concurrentNodeIsBackFlag = false;
                    if (!empty($currentRunProcessInfo)) {
                        foreach ($currentRunProcessInfo as $key => $value) {
                            if (!empty($value['branch_serial'])) {
                                $concurrentNodeIsBackFlag = true;
                            }
                        }
                    }
                    if ($concurrentNodeIsBackFlag && count($currentRunProcessInfo) > 1) {
                        // 获取每个未提交的并发节点所在分支的第一个流转节点信息，并获取这个节点在流程设置里的分支序号，用此序号来匹配后面提交时需要用哪一个process_id来对应提交，因为存在跨节点退回的情况，所以以这种方式判断最准确，如果通过退回的来源节点判断容易出错
                        $concurrentAtFlowSerial = $currentRunProcessInfo[0]['flow_serial'];
                        $conCurrentFirstNodeList = app($this->flowRunProcessRepository)->getFlowRunProcessList(['fields' => ['is_back','flow_serial','branch_serial','process_id','flow_process'],'search' => ['run_id' => [$param["run_id"]], 'flow_serial' => [$concurrentAtFlowSerial], 'process_serial' => [1],'host_flag' => [1]] , 'returntype' =>'array', 'relationNodeInfo' => '1']);
                        $tempConcurrentFirstNodeList = [];
                        if (count($conCurrentFirstNodeList)) {
                            foreach ($conCurrentFirstNodeList as $key => $value) {
                                if (!empty($value['flow_run_process_has_one_flow_process']['branch'])) {
                                    $tempConcurrentFirstNodeList[$value['branch_serial']] = $value['flow_run_process_has_one_flow_process']['branch'];
                                }
                            }
                        }
                        foreach ($currentRunProcessInfo as $key => $value) {
                            if (!empty($value['branch_serial']) && !empty($tempConcurrentFirstNodeList[$value['branch_serial']])) {
                                $tempConcurrentProcessIdArray[$tempConcurrentFirstNodeList[$value['branch_serial']]] = $value['process_id'];
                            }
                        }
                    }
                    // 循环流出节点来进行提交操作
                    $recordConcurrentAtProcessIds = []; // 记录并发节点所在process_id，可能会有多个，用于统一提交标记哪几个process_id的为已提交的状态，因为存在退回的并发节点会触发未触发过的分支，未触发过的分支如果用任意process_id来提交会验证没有办理权限
                    foreach($branchProcess as $branchProcessId){
                        $checkConcurrentIsBack = false;
                        $param['process_id'] = $processId;
                        if ($concurrentNodeIsBackFlag) {
                            $branchProcessInfo = app($this->flowProcessRepository)->getFlowProcessList(['fields' => ['branch'],'search' => ['node_id' => [$branchProcessId]],'returntype' => 'first']);
                            if (!empty($branchProcessInfo->branch) && !empty($tempConcurrentProcessIdArray[$branchProcessInfo->branch])) {
                                $param['process_id'] = $tempConcurrentProcessIdArray[$branchProcessInfo->branch];
                                $checkConcurrentIsBack = true;
                                $recordConcurrentAtProcessIds[] = $param['process_id'];
                            }
                        }
                        $processTransactUser = isset($process[$branchProcessId]['handleUserInfo'])?$process[$branchProcessId]['handleUserInfo']:'';
                        if(is_array($processTransactUser)){
                            $processTransactUser = implode(",",$processTransactUser).",";
                        }
                        if (!empty($process[$branchProcessId]['hostUserInfo'])){
                            $param['process_host_user'] = isset($process[$branchProcessId]['hostUserInfo'])?$process[$branchProcessId]['hostUserInfo']:"";
                        } else {
                            $param['process_host_user'] = "";
                        }
                        $param['process_transact_user'] = $processTransactUser;
                        $param['next_flow_process'] = $branchProcessId;
                        $param['concurrent_node_id'] = $param['concurrent_node_id'] ?? $flowProcess;
                        $param['branch_type'] = 1;
                        $param['branch_complete'] = $branchCount==$index?1:0;
                        $param['record_concurrent_process_ids'] = array_unique($recordConcurrentAtProcessIds);
                        $param['branch_process'] = $branchProcess;
                        $param['first_has_executed'] = $index >= 1 ? 1 : 0 ; // 该参数用于只执行一次部分操作，比如提交时选择多个节点抄送，循环会多次插入
                        $param['limit_date'] =  isset($process[$branchProcessId]['limit_date'])?$process[$branchProcessId]['limit_date']:"";
                        $result = app($this->flowService)->postFlowTurning($param, $userInfo,$isAutoSubmit,$submitUuid);
                        $index++;
                    }
                }
            }
    	} else {
            // 如果是监控提交
            if ($monitor) {
                if ($concurrentFlow) {
                    $flowProcessArr = [];
                    $flowProcessUser = [];
                    $flowProcessOrigin = [];
                    $flowProcessIdArr = [];
                    $flowSerial = 1;
                    $branchSerial = 1;
                    $processSerial = 1;
                    $search["search"] = ["run_id" => [$runId], "flow_process" =>[$flowProcess],"process_id" =>[$processId]];
                    $search["order_by"] = ["flow_run_process_id" => "desc"];
                    $search["fields"] = ['flow_serial','branch_serial','process_serial'];
                    if ($flowRunProcessObject = app($this->flowRunProcessRepository)->getFlowRunProcessList($search)) {
                        $flowRunProcessObject = $flowRunProcessObject->toArray();
                        if (!empty($flowRunProcessObject[0]['flow_serial'])) {
                            $flowSerial = $flowRunProcessObject[0]['flow_serial'];
                            $branchSerial = $flowRunProcessObject[0]['branch_serial'];
                            $processSerial = $flowRunProcessObject[0]['process_serial'];
                        }
                    }
                    $dbData = app($this->flowRunProcessRepository)->getFlowRunProcessInfo(['flow_process','process_id','user_id','origin_process','deliver_time'],['run_id'=>$runId,'flow_serial'=>$flowSerial,'process_serial'=>$processSerial,'branch_serial'=>$branchSerial]);
                    foreach($dbData as $v){
                        if (empty($v['deliver_time'])) {
                            $flowProcessArr[$v['flow_process']] = $v['flow_process'];
                            $flowProcessUser[$v['flow_process']] = $v['user_id'];
                            $flowProcessOrigin[$v['flow_process']] = $v['origin_process'];
                            $flowProcessIdArr[$v['flow_process']] = $v['process_id'];
                        }
                    }
                    foreach($flowProcessArr as $v){
                        if ($v != $flowProcess) {
                            $nextProcessInfo = $this->getProcessInfo($param['next_flow_process']);
                            if (empty($nextProcessInfo['merge']) && $flowTurnType != 'back') {
                                continue;
                            }
                        }
                        $processInfo = $this->getProcessInfo($v);
                        $setHostFlagParam = [
                            "user_id" => $flowProcessUser[$v],
                            "flow_process" => $v,
                            "process_id" => $processId,
                            "handle_way" => $processInfo['process_transact_type'],
                            "run_id" => $runId,
                        ];
                        app($this->flowRunService)->setHostFlag($setHostFlagParam);
                        $param['flow_process'] = $v;
                        $param['process_id'] = $flowProcessIdArr[$v];
//                        if ($flowTurnType == 'back') {
//                            $param['next_flow_process'] = $flowProcessOrigin[$v];
//                        }
                        $monitorParam = $param;
                        if (!empty($param['sonFlowInfo'])) {
                            $sonFlowParam = $param['sonFlowInfo'];
                            $sunflowInfo = app($this->flowService)->getSunflowInfo($v);
                            $sunflowArr = [];
                            foreach($sunflowInfo as $value){
                                $sunflowArr[] = $value['receive_flow_id'];
                            }
                            foreach($sonFlowParam as $key => $value){
                                if (!in_array($value['receive_flow_id'],$sunflowArr)) {
                                    unset($sonFlowParam[$key]);
                                }
                            }
                            $monitorParam['sonFlowInfo'] = $sonFlowParam ;
                        }
                        $result = app($this->flowService)->postFlowTurning($monitorParam, $userInfo,$isAutoSubmit,$submitUuid);
                    }
                    return $result;
                } else {
                    $result = app($this->flowService)->postFlowTurning($param, $userInfo,$isAutoSubmit,$submitUuid);
                    return $result;
                }
            } else {
                $result = app($this->flowService)->postFlowTurning($param, $userInfo,$isAutoSubmit,$submitUuid);
                return $result;
            }
    	}
    	return $result;
    }
    // 验证节点信息
    public function checkChartNode($nodeId,$process_to , $nodesortstring = ''){
        if (!$process_to) {
            return false;
        }
        $processInfo =   $this->getProcessInfo($nodeId,false);
        $flowInfo  = $this->getFlowProcessInfo($processInfo['flow_id'] , false);
        $flowInfo = array_column($flowInfo , null ,'node_id');
        if ($nodesortstring) {
            $processInfo['process_to'] =  $nodesortstring;
            $flowInfo[$nodeId]['process_to'] = $nodesortstring ;
        }
        $nodeProcessInfo =   $flowInfo[$process_to];
        $nodeArr = explode(',', $processInfo['process_to']);
		if(!empty($processInfo['merge'])&&!empty($nodeProcessInfo['concurrent']) && $processInfo['sort'] > $nodeProcessInfo['sort']){
			return ['code' => ['flow_merge_concurrent_error', 'flow']];
		}

		if(!empty($processInfo['concurrent'])&&!empty($nodeProcessInfo['merge']) && $nodeProcessInfo['sort'] > $processInfo['sort']){
			return ['code' => ['flow_concurrent_merge_error', 'flow']];
		}
        // 并发分支之间不允许直接连线
        if (!empty($processInfo['branch']) && !empty($nodeProcessInfo['branch']) && $processInfo['branch']!= $nodeProcessInfo['branch']) {
            return ['code' => ['process_can_not_relation', 'flow']];
        }
        // 并发节点不允许越过出口节点直接连并发分支上的节点
        if (!empty($processInfo['concurrent']) && !empty($nodeProcessInfo['branch']) && $nodeProcessInfo['origin_node'] == $processInfo['node_id']) {
            if (!empty($processInfo['process_to']) && !in_array($process_to,  $nodeArr)  ) {
                return ['code' => ['a_node_is_on_two_concurrent_branches_chart', 'flow'], 'dynamic' => trans('flow.a_node_is_on_two_concurrent_branches_chart', ['node_name' => $flowInfo[$process_to]['process_name'] ])];
            }
        }
        // 同一个并发节点出来的节点只能连同一个合并节点
        if (!empty($processInfo['branch']) && !empty($nodeProcessInfo['merge']) && !empty($processInfo['merge_node']) && $processInfo['merge_node']!=$process_to) {
            return ['code' => ['a_concurrent_and_a_branche', 'flow'], 'dynamic' => trans('flow.a_concurrent_and_a_branche', ['node_name' => $flowInfo[$processInfo['merge_node']]['process_name'] ])];
        }
        // 并发分支上节点连接其他节点时只能在该并发源和合并源之间
        if (!empty($processInfo['branch']) ) {
            if ($processInfo['origin_node'] != $process_to && $processInfo['merge_node'] != $process_to) {
                if (empty($nodeProcessInfo['branch'])) {
                    // 判断该节点是否被连过，如果已经被连过，不能再被连【此处去除合并节点，有可能还没确定合并源】
                    foreach ($flowInfo as $k => $v) {
                        if ($nodeProcessInfo['merge'] && empty($processInfo['merge_node'])) {
                            // 此时还未确定合并源，连接的是合并节点，需要判断该合并节点是否已经被连接
                            if ($v['concurrent'] && $v['merge_node'] == $process_to) {
                                return ['code' => ['merge_has_concurrent', 'flow'], 'dynamic' => trans('flow.merge_has_concurrent', ['node_name' => $v['process_name'] ])];
                            }
                        }
                    }
                    // 如果是首节点
                    if ($nodeProcessInfo['head_node_toggle'] == 1 ) {
                        return ['code' => ['concurrent_node_connection', 'flow'], 'dynamic' => trans('flow.concurrent_node_connection', ['node_name' => $flowInfo[$nodeId]['process_name'] ,'node_name1' => $flowInfo[$processInfo['origin_node']]['process_name'] ,'node_name2' => $flowInfo[$nodeId]['process_name'] ,'node_name3' => $flowInfo[$processInfo['merge_node']]['process_name'] ?? trans('flow.merge_node') ])];
                    }
                    // 判断该节点是否已经连向合并节点，如果已经连向合并节点，则不能在连向一个普通节点，会导致不是最后一个节点连向合并节点
                    if (!empty($processInfo['process_to']) && in_array($processInfo['merge_node'], explode(',', $processInfo['process_to'])) && $processInfo['sort'] < $nodeProcessInfo['sort']) {
                        return ['code' => ['to_merge_node_cannot_to_other_nodes', 'flow']];
                    }

                    // 如果是正向流入一个普通节点，还需要判断是否有合并节点退回到这个节点，要保持合并节点只允许退回在分支上的最后一个
                    if (!empty($processInfo['merge_node']) && $processInfo['sort'] < $nodeProcessInfo['sort'] && in_array($nodeId, explode(',', $flowInfo[$processInfo['merge_node']]['process_to'])) && $flowInfo[$nodeId]['sort'] <$flowInfo[$processInfo['merge_node']]['sort']) {
                         return ['code' => ['to_normal_cannot_last_node', 'flow'], 'dynamic' => trans('flow.to_normal_cannot_last_node', ['node_name' => $processInfo['process_name'] ,'node_name1' => $nodeProcessInfo['process_name'] ])];
                    }

                }
                // 并发分支上的节点不能流向多个出口
                if (!empty($nodeArr) && $processInfo['sort'] < $nodeProcessInfo['sort']) {
                    foreach ($nodeArr as $k2 => $v2) {
                        if (!empty($v2) && $processInfo['sort'] < $flowInfo[$v2]['sort'] && $v2 != $process_to) {
                            return ['code' => ['branch_not_support_multiple_exits', 'flow']];
                        }
                    }
                }
            }else if ($processInfo['merge_node'] == $process_to) {
                // 分支上的节点序号要小于合并节点的序号
                if ($processInfo['sort'] > $nodeProcessInfo['sort']) {
                     return ['code' => ['branch_sort_must_smaller', 'flow'], 'dynamic' => trans('flow.branch_sort_must_smaller', ['node_name' => $processInfo['process_name'] ])];
                }
                // 如果连接的的是合并节点，该分支线上的最后一个节点才能连向合并节点
                foreach ($nodeArr as $nk => $nv) {
                    if (!empty($nv) && $processInfo['branch'] == $flowInfo[$nv]['branch'] && $processInfo['sort'] < $flowInfo[$nv]['sort']) {
                        return ['code' => ['connect_to_the_same_merge_node', 'flow']];
                    }
                }
            }
            // 任意一个分支节点连向合并节点都需要判断合并节点是否退回分支上最后一个节点
            if($nodeProcessInfo['merge']) {
                $nodeProcessInfoProcessTo = array_filter(explode(',', $nodeProcessInfo['process_to']));
                foreach ($nodeProcessInfoProcessTo as $tk => $tv) {
                    if ( $nodeProcessInfo['sort'] > $flowInfo[$tv]['sort'] ) {
                        if ($flowInfo[$tv]['origin_node'] !=$processInfo['origin_node']) {
                            return ['code' => ['merging_node_rollback', 'flow']];
                        } else {
                            // 同一个并发源时还需要判断是否是分支上的最后一个节点
                            $x = array_filter(explode(',', $flowInfo[$tv]['process_to']));
                            foreach ($x as $xk => $xv) {
                               if ($flowInfo[$xv]['sort'] > $flowInfo[$tv]['sort'] && $xv != $process_to) {
                                  return ['code' => ['merging_node_rollback', 'flow']];
                               }
                            }
                        }
                    }
                }
            }
        }
        //合并节点退回到分支上的节点： 合并节点只允许退回在分支上的最后一个
        if(!empty($processInfo['merge']) && $processInfo['sort'] > $nodeProcessInfo['sort']) {
            // 合并节点退回到分支节点上
            if (!empty($nodeProcessInfo['branch'])) {
                if ($nodeProcessInfo['merge_node'] != $nodeId ) {
                // 此时不在分支上或者不在同一个分支源内
                    return ['code' => ['merging_node_rollback', 'flow']];
                }
                $nodeArr = explode(',', $nodeProcessInfo['process_to']);
                // 判断是否是最后一个节点
                foreach ($nodeArr as $nk => $nv) {
                        if (!empty($nv) && $nodeProcessInfo['branch'] == $flowInfo[$nv]['branch'] && $nodeProcessInfo['sort'] < $flowInfo[$nv]['sort']) {
                             return ['code' => ['merging_node_rollback', 'flow']];
                        }
                }
            } else {
                // 合并节点退回到普通节点上， 判断该合并节点是否有并发源[只需要判断有没有节点的merge_node是该节点]，有并发源合并节点此时判断才有意义
                foreach ($flowInfo as $k => $v) {
                        if (!empty($v['merge_node']) && $v['merge_node'] == $nodeId ) {
                              return ['code' => ['merging_node_rollback', 'flow']];
                        }
                }

            }

        }
        // 如果一个并发节点已经对应了一个合并节点，那么这个并发节点不能再连接合并源之后的节点
        if (!empty($processInfo['concurrent']) && !empty($processInfo['merge_node'])) {
             $afterMergeNodes = $this->nodesAfterMerging($flowInfo ,$processInfo['merge_node'] , true );
             if (in_array($process_to, $afterMergeNodes)) {
                return ['code' => ['concurrent_not_support_after_merge', 'flow'], 'dynamic' => trans('flow.concurrent_not_support_after_merge', ['node_name' => $flowInfo[$processInfo['merge_node']]['process_name'] ])];
             }
        }
        // 只允许对应的合并节点和分支线上的节点退回到分支上的节点
        if ($processInfo['sort'] > $nodeProcessInfo['sort'] && $nodeProcessInfo['branch']) {
            if ( ($nodeProcessInfo['branch'] == $processInfo['branch']) || (empty($nodeProcessInfo['merge_node']) && $processInfo['merge']) || $nodeProcessInfo['merge_node'] == $nodeId) {
                // 1 满足在同一个分支上 2未确定合并源时，且连线的节点是一个合并节点 3是对应的合并源
            } else {
                return ['code' => ['to_branch_must_be_same_merge', 'flow'], 'dynamic' => trans('flow.to_branch_must_be_same_merge', ['node_name' => $processInfo['process_name'],'node_name1' => $nodeProcessInfo['process_name']   ])];
            }
        }
        //如果从并发节点连向一个普通节点，而这个普通节点所在的分支上又存在其他分支的节点
        if ($processInfo['concurrent'] && !$nodeProcessInfo['branch'] && $nodeProcessInfo['sort'] > $processInfo['sort']) {
            $branches = $this->recursivelyGetBranch( $flowInfo ,$process_to  );
            $branches =  $branches['branches'];
            if (count($branches)) {
                return ['code' => ['a_node_is_on_two_concurrent_branches_chart', 'flow'], 'dynamic' => trans('flow.a_node_is_on_two_concurrent_branches_chart', ['node_name' => $flowInfo[$branches[0]]['process_name'] ])];
            }
        }
		return false;
    }
    // 获取流程节点数组
    public function getFlowConcurrentArr($nodeInfo,$type = 0,$cache = true){
    	$concurrentId = 0;
    	$concurrentArr = [];
        $nodeId = $nodeInfo['node_id'] ?? '';
        if (empty($nodeId)) {
            return $concurrentArr;
        }
    	if(!isset($nodeInfo['flow_id'])){
			return $concurrentArr;
    	}
    	$flowProcessInfo  = $this->getFlowProcessInfo($nodeInfo['flow_id'],$cache);
    	$flowProcessArr = [];
    	$concurrentNodeArray = [];
    	foreach($flowProcessInfo as $v){
    		$flowProcessArr[$v['node_id']] = $v;
    		if(!empty($v['concurrent']) && !empty($v['process_to'])){
    			if(empty($concurrentId)){
    				$concurrentId = $v['node_id'];
    			}
    			$concurrentNodeArray[$v['node_id']] = $v['node_id'];
    		}
    	}
    	if($type == 1) {
    		$itemArray = [];
			foreach($concurrentNodeArray as $v){
				$itemArray[$v] = $this->getFlowProcessTo($v,$flowProcessArr[$v]['process_to'],[],$flowProcessArr,0);
			}
    	}
    	if(!empty($concurrentId) && isset($flowProcessArr[$concurrentId])){
			$processTo = explode(",",$flowProcessArr[$concurrentId]['process_to']);
			foreach($processTo as $v){
    			$concurrentArr[$v] = $this->getFlowProcessToData($v,$flowProcessArr,[]);
    		}
    	}
    	return $concurrentArr;
    }
    // 获取流程流向数据
	public function getFlowProcessToData($nodeId,$flowProcessArr,$data){
		if (!empty($flowProcessArr[$nodeId]['merge'])) {
			return $data;
		}
		if (isset($flowProcessArr[$nodeId]['process_to'])) {
			$processTo = explode(",",$flowProcessArr[$nodeId]['process_to']);
			if(!empty($processTo)){
				foreach($processTo as $v){
					if (in_array($v,$data)) {
						continue;
					}
					if (!in_array($v,$data)) {
						if (!empty($flowProcessArr[$v]['merge'])) {
							return $data;
						}
						if (!empty($flowProcessArr[$v]['concurrent'])) {
							continue;
						}
						$data[] = $v;
						$result = $this->getFlowProcessToData($v,$flowProcessArr,$data);
						$data = array_merge($data,$result);
					}
				}
			}
		}
    	return $data;
    }
    // 获取流程节点信息
    public function getFlowProcessTo($nodeId,$processTo,$processArr,$flowProcessArr,$concurrentId){
    	$current = empty($concurrentId)?true:false;
    	if (!empty($flowProcessArr[$nodeId]['merge'])){
    		return $processArr;
    	}
    	if (empty($nodeId)) {
    		return $processArr;
    	}
    	if (empty($processTo)) {
    		return $processArr;
    	}
    	if(!empty($processTo)){
    		$processTo = explode(",",$processTo);
    		foreach($processTo as $v){
				if (!isset($processArr[$nodeId])) {
					if($current){
						$processArr[$v] = [];
						$concurrentId = $v;
					}else{
						$processArr[$concurrentId][]=$v;
					}
					$processArr = $this->getFlowProcessTo($v,$flowProcessArr[$v]['process_to'],$processArr,$flowProcessArr,$concurrentId);
				} else {
					$processArr[$concurrentId][]=$v;
				}
    		}
		}
    	return $processArr;
    }
    // 是否显示结束选项
    public function isShowFinishOption($nodeInfo){
    	$branch =isset($nodeInfo['branch']) ? $nodeInfo['branch'] : 0;
        $concurrent =isset($nodeInfo['concurrent']) ? $nodeInfo['concurrent'] : 0;
        if ($branch || $concurrent) {
            return 0;
        }
        return 1;
    }
    // 获取并发出来的节点数量
    public function getConcurrentProcessNum($runId,$flowProcess,$flowId){
		$num = 0;
		$nodeNum = 0;
		$dbInfo = app($this->flowProcessRepository)->entity->select(['node_id','concurrent','merge'])->where(['flow_id'=>$flowId])->orderBy('node_id','ASC')->get()->toArray();
		foreach($dbInfo as $value){
			if(!empty($value['concurrent'])){
				$dbData = app($this->flowRunProcessRepository)->entity->select(['flow_process','process_id'])->where(['run_id'=>$runId,'flow_process'=>$value['node_id'],'host_flag'=>1])->get();
				if (!empty($dbData)) {
					$dbData = $dbData->toArray();
				}
				foreach($dbData as $item){
					$processId = $item['process_id'] + 1;
					$nodeData = app($this->flowRunProcessRepository)->entity->select(['flow_process','process_id'])->where(['run_id'=>$runId,'origin_process'=>$item['flow_process']])->get();
					if (!empty($nodeData)) {
						$nodeData = $nodeData->toArray();
						$nodeArray =[];
						foreach($nodeData as $v){
							$nodeArray[$v['flow_process']] = $v['flow_process'];
						}
						$nodeNum = count($nodeArray);
						if ($num < $nodeNum) {
							$num = $nodeNum;
						}
					}
				}
			}
		}
		return $num;
    }
    // 获取流入节点数量
    public function getFlowInProcessNum($runId,$flowProcess,$originProcessId = null){
    	$num = 0;
    	$runObject = app($this->flowRunRepository)->getDetail($runId);
    	$flowRunProcessArray = [];
    	$flowProcessInfo = $this->getFlowProcessInfo($runObject->flow_id);
    	if($flowProcessInfo){
    		foreach($flowProcessInfo as $v){
    			$processTo = explode(',',$v['process_to']);
    			if(count($processTo)>0){
    				foreach($processTo as $nodeId){
    					if(!empty($nodeId)){
    						$flowRunProcessArray[$v['node_id']][$nodeId] = ['node_id'=> $nodeId];
    					}
    				}
    			}
    		}
    	}
    	$nodeArray = [];
    	foreach($flowRunProcessArray as $key => $value){
			foreach($value as $k => $v){
				if($v['node_id']==$flowProcess){
					if(!in_array($key,$nodeArray)){
						$nodeArray[] = $key;
					}
				}
			}
    	}
    	$dbData = app($this->flowRunProcessRepository)->entity->select(['flow_process'])->where(['run_id'=>$runId])->whereIn('flow_process', $nodeArray)->whereRaw("(deliver_time IS NOT NULL)")->get();
    	if (!empty($dbData)) {
    		$dbData = $dbData->toArray();
    		$nodeArray =[];
    		foreach($dbData as $v){
    			$nodeArray[$v['flow_process']] = $v['flow_process'];
    		}
    		$dbInfo = app($this->flowRunProcessRepository)->entity->select(['flow_process','process_id','origin_process','outflow_process'])->where(['run_id'=>$runId,'flow_process'=>$flowProcess])->whereIn('origin_process',$nodeArray)->orderBy('flow_run_process_id','ASC')->get();
    		if (!empty($dbInfo)) {
    			$dbInfo = $dbInfo->toArray();
    			$flowProcessArray = [];
    			foreach($dbInfo as $value){
    				if (!empty($value['origin_process'])){
    					if (!empty($value['outflow_process'])) {
							continue;
    					}
    					$flowProcessArray[$value['origin_process']] = $value['origin_process'];
    					$processId = $value['process_id'] + 1;
    					$dbProcess = app($this->flowRunProcessRepository)->entity->select(['flow_process','process_id','origin_process'])->where(['run_id'=>$runId,'flow_process'=>$value['origin_process'],'origin_process'=>$flowProcess,'process_id'=>$processId])->get();
    					if (!empty($dbProcess) && !empty($dbProcess->toArray())) {
    						unset($flowProcessArray[$value['origin_process']]);
    					}
    				}
    			}
    			$num = count($flowProcessArray);
    		}
    	}
    	if (!empty($originProcessId)) {
    		$processId = $originProcessId-1;
    		$dbInfo = app($this->flowRunProcessRepository)->entity->select(['flow_process','process_id','origin_process','outflow_process'])->where(['run_id'=>$runId,'process_id'=> $processId,'host_flag'=>[1]])->whereRaw("(deliver_time IS NOT NULL)")->orderBy('flow_run_process_id','ASC')->get();
    		if(!empty($dbInfo)) {
    			$dbInfo = $dbInfo->toArray();
    			$outflowNum = 0;
    			foreach($dbInfo as $v){
    				$arr = explode(',',$v['outflow_process']);
    				if (in_array($flowProcess,$arr)) {
    					$outflowNum++;
    				}
    			}
    		}
    	}
    	$flowOutNum = $this->getFlowOutProcessNum($runId,$flowProcess);
    	$num += $flowOutNum;
    	return $num;
    }

    // 获取流程流出节点数量
    public function getFlowOutProcessNum($runId,$flowProcess){
    	$num = 0;
    	$nodeNum = 0;
    	$runObject = app($this->flowRunRepository)->getDetail($runId, false , ['flow_id']);
    	if (empty($runObject)) {
    		return 0;
    	}
    	$flowId = $runObject->flow_id;
    	$mergeProcess = 0;
    	$dbInfo = app($this->flowProcessRepository)->entity->select(['node_id','concurrent','merge'])->where(['flow_id'=>$flowId])->orderBy('node_id','ASC')->get()->toArray();
    	foreach($dbInfo as $value){
    		if (!empty($value['merge']) && empty($mergeProcess)){
    			$mergeProcess = $value['node_id'];
    		}
    	}
    	foreach($dbInfo as $value){
    		if(!empty($value['concurrent'])){
    			$dbData = app($this->flowRunProcessRepository)->entity->select(['flow_process','process_id'])->where(['run_id'=>$runId,'flow_process'=>$value['node_id'],'host_flag'=>1])->get();
    			if (!empty($dbData)) {
    				$dbData = $dbData->toArray();
    			}
    			foreach($dbData as $item){
    				$processId = $item['process_id'] + 1;
    				$nodeData = app($this->flowRunProcessRepository)->entity->select(['flow_process','process_id','deliver_time'])->where(['run_id'=>$runId,'origin_process'=>$item['flow_process']])->get();
    				if (!empty($nodeData)) {
    					$nodeData = $nodeData->toArray();
    					$nodeArray =[];
    					foreach($nodeData as $v){
    						if (!isset($nodeArray[$v['flow_process']])){
    							$nodeArray[$v['flow_process']] = false;
    						}
    						if (!empty($v['deliver_time'])) {
    							$nodeArray[$v['flow_process']] = true;
    						}
    					}
    					$nodeNum = 0;
    					foreach($nodeArray as $k => $v){
							if ($v){
								$data = $this->getProcessInfo($k);
								$processTo = explode(',',trim($data['process_to'],','));
								if (empty($processTo) || (count($processTo)==1 && empty($processTo[0]))){
									$nodeNum++;
								}
							}
    					}
    					if ($num < $nodeNum) {
    						$num = $nodeNum;
    					}
    				}
    			}
    		}
    	}
    	return $num;
    }
    // 步骤是否强制合并
    public function isForceMerge($runId,$flowProcess,$userId =null,$processId =null,$flowTurnType ='turn')
    {
    	$result = 0;
    	if ($flowTurnType != 'turn') {
			return $result;
    	}
    	$processInfo = $this->getProcessInfo($flowProcess);
    	$combineType = $processInfo['merge'];
    	if(! $runId) {
    		return $result;
    	}
    	// 非强制合并节点直接返回
    	if($combineType !=2 ){
    		return $result;
    	} else {
    		$runObject = app($this->flowRunRepository)->getDetail($runId);
    		$isConcurrentFlow = $this->isConcurrentFlow($runObject->flow_id);
    		if ($isConcurrentFlow) {
    			$concurrentNum = $this->getConcurrentProcessNum($runId,$flowProcess,$runObject->flow_id);
    			$flowInNum = $this->getFlowInProcessNum($runId,$flowProcess,$processId);
                if($concurrentNum != $flowInNum && $concurrentNum > 1){
    				$result = 1;
    			}
    		}
    	}
    	if($result == 1 && $combineType == 2 && $userId && $processId) {
    		$dbData = app($this->flowRunProcessRepository)->entity->select(['flow_process'])->where(['run_id'=>$runId,"process_id" => [$processId-1]])->get()->toArray();
    		foreach($dbData as $v){
				$processInfo = $this->getProcessInfo($v['flow_process']);

    			if(!empty($processInfo['process_concourse'])){
    				app($this->flowRunProcessRepository)->updateFlowRunProcessData(["data" => ["saveform_time" => date("Y-m-d H:i:s", time()),"transact_time" => date("Y-m-d H:i:s", time()),"process_flag" => 3], "wheres" => ["run_id" => [$runId], "user_id" => [$userId], "host_flag" => '0','flow_process'=> [$v['flow_process']]], "whereRaw" => ["((saveform_time = '0000-00-00 00:00:00') OR (saveform_time IS NULL))"]]);
    				$concurrentNum = $this->getConcurrentProcessNum($runId,$flowProcess,$runObject->flow_id);
    				$flowInNum = $this->getFlowInProcessNum($runId,$flowProcess,$processId);
    				if($concurrentNum == $flowInNum){
    					return 0;
    				}
    			}
    		}

    	}
    	return $result;
    }
    /**
     * 强制合并节点下判断是否流程是否全部汇总或到达
     * @param $flowId
     * @param $runId
     * @param $mergeProcess 合并节点
     * @param int $mergeProcessSort 合并节点的 sort
     * @return bool
     */
    public function isFinishedMergeProcess($flowId, $runId, $mergeProcess, $mergeProcessSort = 0)
    {
        // 获取所有的节点信息
        $flowProcessInfo = $this->getFlowProcessInfo($flowId);
        $flowProcessnodeKeyInfo = array_column($flowProcessInfo , null ,'node_id');
        // 获取合并节点信息
        $flowRunMergerInfo = app($this->flowRunProcessRepository)->getFlowRunProcessList([
            'search' => ['run_id' => [$runId], 'flow_process' => [$mergeProcess]],
            'order_by' => ["flow_serial" => "desc"], // 取该合并节点最新的一条
            'returntype' => 'first',
            "fields" =>  ['concurrent_node_id' , 'flow_serial'] // 用于判断是否从合并节点并发而来
        ]);
        if ($flowRunMergerInfo && $flowRunMergerInfo->concurrent_node_id) {
            // 说明此时合并节点是从并发来的 ，根据concurrent_node_id ，flow_serial取数据
             $flowRunSameFlowSerial = app($this->flowRunProcessRepository)->getFlowRunProcessList([
                    'search' => ['run_id' => [$runId], 'concurrent_node_id' => [$flowRunMergerInfo->concurrent_node_id] ,'flow_serial' => [$flowRunMergerInfo->flow_serial]],
                    'order_by' => ["process_serial" => "desc", "host_flag" => "desc", "user_run_type"=> "asc"], // 根据分支线上倒序排,
                    'returntype' => 'array',
                    "fields" =>  ['branch_serial' , 'process_serial' , 'flow_process' , 'flow_run_process_id' , 'user_run_type'] //
                ]);
             $branchHasJudged = []; // 该分支是否判断
             foreach ($flowRunSameFlowSerial as $key => $value) {
                 if (!in_array($value['branch_serial'], $branchHasJudged)  && $value['flow_process']!= $mergeProcess &&  $value['user_run_type'] == 1) {
                    // 1 如果是分支上节点,且有连向合并节点  2 或者不是分支上的节点
                    if ( ($flowProcessnodeKeyInfo[$value['flow_process']]['branch'] && $flowProcessnodeKeyInfo[$value['flow_process']]['line_to_merge_node']) || !$flowProcessnodeKeyInfo[$value['flow_process']]['branch']) {
                       return false;
                    }
                    // 此时可以取到分支上的最新节点，即 $value['flow_process'] , 直接判断
                 }
                 array_push($branchHasJudged, $value['branch_serial']);
             }
        }
        return true;

    }

    // 多节点判断不选人提交
    public function targetProcessVerifyWithoutSelectUser($param,$processArray){
    	$submitWithoutDialog = false;
    	$submitInfo = [];
    	$nodeId = isset($param['flowProcess'])?$param['flowProcess']:'';
    	$processInfo = $this->getProcessInfo($nodeId);
    	if(!empty($processArray) && !empty($processInfo['concurrent'])){
    		$submitArray = [];
    		$verifyStatus = true;
			foreach($processArray as $k => $v){
				$verifyResult = app($this->flowService)->targetProcessVerifyWithoutSelectUser(["flowProcess" => $param["flowProcess"], "runId" => $param["runId"], "flowId" => $param["flowId"], "node_id" => $v["node_id"], "nodeInfo" => $v, "userId" => $param["currentUser"], "processId" => $param["processId"]]);
				if (!$verifyResult["submitWithoutDialog"]){
					$verifyStatus = false;
				} else {
					$submitArray[$v["node_id"]] = $verifyResult["submitInfo"];
				}
			}
			if ($verifyStatus && !empty($submitArray)){
				$submitWithoutDialog = true;
				$submitNodeArray = [];
				foreach($submitArray as $k => $v){
					$item = [];
					$item['hostUserInfo'] = isset($v['process_host_user'])?$v['process_host_user']:'';
					$item['handleUserInfo'] = isset($v['process_transact_user'])?$v['process_transact_user']:'';
					$item['nodeId'] = isset($v['next_flow_process'])?$v['next_flow_process']:'';
					$submitNodeArray[] = $item;
					$submitInfo = $v;
					$submitInfo['transactProcessInfo']['currentProcess'] = $v['next_flow_process'];
				}
				$submitInfo['transactProcessInfo']['process'] = $submitNodeArray;
			}
    	}
    	$result['submitWithoutDialog'] = $submitWithoutDialog;
    	$result['submitInfo'] = $submitInfo;
    	if (isset($result['submitInfo']['transactProcessInfo']['process']) && count($result['submitInfo']['transactProcessInfo']['process']) > 1 ){
    		$result['submitWithoutDialog'] = false;
    		$result['submitInfo'] = [];
    	}
    	return $result;
    }
    // 流程是否已完成
	public function isFlowCompelte($flowId,$runId,$flowProcess){
    	$result = true;
    	$flowRunProcessArray = [];
    	$data = $this->getProcessInfo($flowProcess);
    	if(isset($data['merge']) && $data['merge']==1){
    		return $result;
    	}
    	$processArr = [];
    	$dbData = app($this->flowRunProcessRepository)->getFlowRunProcessInfo(['flow_process','host_flag','deliver_time','process_id'],['run_id'=>$runId]);
    	foreach($dbData as $v){
    		if (!isset($processArr[$v['flow_process']])){
    			$processArr[$v['flow_process']] = false;
    		}
			if (!empty($v['deliver_time'])) {
				$processArr[$v['flow_process']] = true;
			}
    	}
    	foreach($processArr as $k =>$v){
			if (!$v){
				$processInfo = $this->getProcessInfo($k);
				if (!empty($processInfo['merge']) && $flowProcess!= $k) {
					return false;
				}
			}
    	}
    	$flowRunProcessInfo = $this->getFlowRunProcessInfo($runId);
    	if($flowRunProcessInfo){
    		$flowStatus = [];
    		$flowProcessInfo = $this->getFlowProcessInfo($flowId);
    		if($flowProcessInfo){
    			foreach($flowProcessInfo as $v){
    				$processTo = explode(',',$v['process_to']);
    				if(count($processTo)>0){
    					foreach($processTo as $nodeId){
    						$flowRunProcessArray[$v['node_id']][$nodeId] = ['node_id'=> $nodeId];
    					}
    				}
    			}
    		}
    		foreach($flowRunProcessInfo as $v){
    			if($v['host_flag'] == '1' && !empty($v['deliver_time'])){
    				$flowStatus[$v['flow_process']] = $v['flow_process'];
    			}
    		}
			foreach($flowRunProcessInfo as $v){
				if($v['host_flag'] == '1' && empty($v['deliver_time'])){
					if($v['flow_process']!=$flowProcess){
						$result = false;
						if(!empty($flowRunProcessArray[$v['flow_process']])){
							$processType = false;
							foreach($flowRunProcessArray[$v['flow_process']] as $item){
								if(isset($flowStatus[$item['node_id']])){
									$processType = true;
								}else{
									if($item['node_id']==$flowProcess){
										$processType = true;
									}
								}
							}
						}
						if(!$result){
							return $result;
						}
					}
				}
			}
    	}
    	return $result;
    }
    // 获得流程合并节点
    public function getProcessMergeNode($flowProcess,$processTo,$flowProcessArr) {
    	if(!empty($processTo)){
    		$processToArray = explode(",",$processTo);
    		foreach($processToArray as $v){
    			if(!empty($v)) {
    				if (!empty($flowProcessArr[$v]['merge'])){
    					return $v;
    				}
    				$flowMergeNode = $this->getProcessMergeNode($v,$flowProcessArr[$v]['process_to'],$flowProcessArr);
    				if (!empty($flowMergeNode)) {
    					return $flowMergeNode;
    				}
    			}
    		}
    	}
    	return 0;
    }
    // 是否是关联节点
    public function isRelationNode($flowProcess,$targetNode){
		$result = false;
		if (!empty($flowProcess) && !empty($targetNode)){
			$processInfo = $this->getProcessInfo($targetNode);
			if (!empty($processInfo['process_to'])) {
				$processToArray = explode(",", trim($processInfo['process_to'], ","));
				if (in_array($flowProcess,$processToArray)){
					$result = true;
				}
			}
		}
		return $result;
    }
	// 节点是否操作完成
    public function isEntireProcessComplete($runId,$flowId,$flowProcess =null){
    	$result = true;
    	$processArr = [];
    	$dbData = app($this->flowRunProcessRepository)->getFlowRunProcessInfo(['flow_process','process_id','deliver_time','host_flag'],['run_id'=>$runId]);
    	foreach($dbData as $v){
    		if (!isset($processArr[$v['flow_process']])){
    			$processArr[$v['flow_process']] = false;
    		}
    		if (!empty($v['deliver_time'])) {
    			$processArr[$v['flow_process']] = true;
    		}
    	}
    	foreach($processArr as $k =>$v){
    		if (!$v){
    			if (empty($flowProcess) || $k != $flowProcess){
    				$result = false;
    				return $result;
    			}
    		}
    	}
    	return $result;
    }
    //更新流程节点序号
    public function updateProcessSerial($flowId) {
    	if (empty($flowId)) {
    		return false;
    	}
    	$isConcurrentFlow = $this->isConcurrentFlow($flowId);
    	if ($isConcurrentFlow) {
    		$flowProcessArr = [];
    		$flowProcessInfo = $this->getFlowProcessInfo($flowId);
    		foreach($flowProcessInfo as $v){
    			$flowProcessArr[$v['node_id']] = $v;
    			app($this->flowProcessRepository)->updateData(['branch_serial'=>0,'process_serial'=>0], ['node_id' => $v['node_id']]);
    		}
    		if($flowProcessInfo){
    			foreach($flowProcessInfo as $v){
    				if(!empty($v['concurrent'])){
    					$this->updateProcessNode($flowProcessArr,$v['node_id']);
    				}
    			}
    		}
    	}
    }
    // 更新节点信息
    public function updateProcessNode($flowProcessArr,$flowProcess,$RedBlackNode = [], $branchSerial = 0, $processSerial = 0){
    	$nodeInfo = $flowProcessArr[$flowProcess];
    	if (!empty($branchSerial)) {
    		if (!empty($nodeInfo['merge']) || !empty($nodeInfo['concurrent'])){
    			return 0;
    		}
    	}
    	$RedBlackNode[] = $flowProcess;
    	$processTo = explode(',',trim($nodeInfo['process_to'],','));
    	foreach($processTo as $value){
    			if (in_array($value,$RedBlackNode)) {
    				continue;
    			}
    			$RedBlackNode[] = $value;
    			if (!isset($flowProcessArr[$value])) {
    				return 0;
    			}
    			$searchNode = $flowProcessArr[$value];
    			if (!empty($searchNode['concurrent'])){
    				continue;
    			}
    			if (!empty($searchNode['merge'])) {
    				continue;
    			} else {
    				if (empty($processSerial)) {
    					$branchSerial++;
    				}
    				$processSerialKey = $processSerial;
    				if (empty($processSerialKey)) {
    					$processSerialKey = 1;
    				} else {
    					$processSerialKey++;
    				}
    				app($this->flowProcessRepository)->updateData(['branch_serial'=>$branchSerial,'process_serial'=>$processSerialKey], ['node_id' => $value]);
    				$this->updateProcessNode($flowProcessArr,$value,$RedBlackNode,$branchSerial,$processSerialKey);
    			}
    	}
    	return 0;
    }
    // 获取流向节点
    public function flowRelationNode($flowProcess){
    	$result = [];
    	if (!empty($flowProcess)){
    		$processInfo = $this->getProcessInfo($flowProcess);
    		if (!empty($processInfo['process_to'])) {
    			$result = explode(",", trim($processInfo['process_to'], ","));
    		}
    	}
    	return $result;
    }
    // 流程流入的节点
    public function flowTurnInNode($flowId,$flowProcess){
    	$result = [];
    	if (!empty($flowProcess)){
    		$flowProcessInfo = $this->getFlowProcessInfo($flowId);
    		if($flowProcessInfo){
    			foreach($flowProcessInfo as $v){
    				$processTo = explode(',',$v['process_to']);
    				if(count($processTo)>0){
    					foreach($processTo as $nodeId){
    						if(!empty($nodeId)){
    							if($nodeId == $flowProcess){
    								if(!in_array($v['node_id'],$result)){
    									$result[] = $v['node_id'];
    								}
    							}
    						}
    					}
    				}
    			}
    		}
    	}
    	return $result;
    }
    // 判断是否是合并节点
    public function isProcessMergeNode($flowProcessArr,$flowProcess,$RedBlackNode = []){
    	$nodeInfo = $flowProcessArr[$flowProcess];
    	$RedBlackNode[] = $flowProcess;
    	foreach($flowProcessArr as $v){
			$processTo = explode(',',trim($nodeInfo['process_to'],','));
			foreach($processTo as $value){
				if (in_array($value,$RedBlackNode)) {
					continue;
				}
				$RedBlackNode[] = $value;
				if (!isset($flowProcessArr[$value])) {
					return 0;
				}
				$searchNode = $flowProcessArr[$value];
				if (!empty($searchNode['concurrent'])){
					return 0;
				}
				if (!empty($searchNode['merge'])) {
					return $value;
				} else {
					return $this->isProcessMergeNode($flowProcessArr,$value,$RedBlackNode);
				}
				break;
			}
    	}
    	return 0;
    }
    // 判断是否多节点设置合并属性
    public function isProcessMultiNode($flowProcessArr,$flowProcess){
    	$nodeInfo = $flowProcessArr[$flowProcess];
    	foreach($flowProcessArr as $v){
    		$processTo = explode(',',trim($v['process_to'],','));
    		if (in_array($flowProcess,$processTo)) {
    			$nodeId = $v['node_id'];
    			$searchNode = $flowProcessArr[$nodeId];
    			if (!empty($searchNode['merge'])){
    				return 0;
    			}
    			if (!empty($searchNode['concurrent'])) {
    				return $nodeId;
    			} else {
    				return $this->isProcessMultiNode($flowProcessArr,$nodeId);
    			}
    			break;
    		}
    	}
    	return 0;
    }
    // 判断是否多节点设置并发属性
	public function isMultiConcurrent($flowId,$flowProcess,$param,$merge = 0) {
		$result = false;
		$flowProcessArr = [];
		$flowProcessInfo = $this->getFlowProcessInfo($flowId);
		foreach($flowProcessInfo as $v){
			$flowProcessArr[$v['node_id']] = $v;
		}
		if (!empty($param['concurrent'])) {
			$nodeId = $this->isProcessMultiNode($flowProcessArr,$flowProcess);
			if (!empty($nodeId) && $nodeId != $flowProcess) {
				return [1,$nodeId];
			}
		}
		if (!empty($param['merge'])) {
			$nodeId = $this->isProcessMergeNode($flowProcessArr,$flowProcess);
			if (!empty($nodeId)) {
				return [2,$nodeId];
			}
		}
    	return $result;
	}
	// 获取来源用户名称
	public function getOriginUserName($originUser,$list) {
		if (empty($originUser)) {
			return '';
		}
		static $originArray;
		if (empty($originArray)) {
			static $originList;
			if (empty($originList)) {
				foreach ($list as $v){
					$originList[] = $v['origin_user'];
				}
				$userNames = app($this->userRepository)->getUserNames($originList);
				foreach($userNames as $v){
					$originArray[$v['user_id']] = $v['user_name'];
				}
			}
		}
		$originUserName = isset($originArray[$originUser])?$originArray[$originUser]:'';
		return $originUserName;
	}
	// 获取来源节点名称
	public function getOriginProcessName($originProcess,$list) {
		if (empty($originProcess)) {
			return '';
		}
		static $originArray;
		if (empty($originArray)) {
			static $originList;
			if (empty($originList)) {
				foreach ($list as $v){
					if (isset($v['origin_process'])) {
						$originList[] = $v['origin_process'];
					}
				}
				$processNames = app($this->flowProcessRepository)->getProcessNames($originList);
				foreach($processNames as $v){
					$originArray[$v['node_id']] = $v['process_name'];
				}
			}
		}
		$originProcessName = isset($originArray[$originProcess])?$originArray[$originProcess]:'';
		return $originProcessName;
	}
	// 判断是否是循环节点
	public function hascirculationNode($flowId,$runId,$nodeId,$targetNodeId) {
		$result = false;
		$search = ['search' => ['run_id' => [$runId]],'returntype'=>'array'];
		$search['fields'] = ['flow_process','origin_process'];
		$runInfo = app($this->flowRunProcessRepository)->getFlowRunProcessList($search);
		if (count($runInfo) > 0) {
			foreach($runInfo as $value){
				if (isset($value['origin_process']) && $value['origin_process']== $nodeId && $value['flow_process']== $targetNodeId) {
					$result = true;
				}
			}
		}
		return$result;
	}
	// 提交流程节点
	public function submitFlowProcess($runId,$flowProcess,$nextFlowProcess,$processId,$userId,$param,$processHostUser) {
		if (!empty($nextFlowProcess)) {
			$flowRunProcessData = ["run_id" => $runId,"search" => ["process_id" => [$processId], "run_id" => [$runId], "flow_process" => [$flowProcess],"host_flag" => [1]]];
			$flowRunProcessData['fields'] = ['outflow_process','outflow_user'];
			if ($flowRunProcessObject = app($this->flowRunProcessRepository)->getFlowRunProcessList($flowRunProcessData)) {
				$flowRunProcessObject = $flowRunProcessObject->first();
				if (!empty($flowRunProcessObject->outflow_process)) {
				if(strpos($flowRunProcessObject->outflow_process,strval($nextFlowProcess)) === false){
					if (!empty($flowRunProcessObject->outflow_process)){
						$nextFlowProcess = $flowRunProcessObject->outflow_process .= ','.$nextFlowProcess;
					}
					if (!empty($flowRunProcessObject->outflow_user)){
						$processHostUser = $flowRunProcessObject->outflow_user .= ','.$processHostUser;
					}
					app($this->flowRunProcessRepository)->updateData(["outflow_process" =>$nextFlowProcess,"outflow_user" =>$processHostUser],["run_id" =>[$runId],"flow_process"=>[$flowProcess],"process_id" => [$processId],"host_flag" => '1']);
					app($this->flowRunProcessRepository)->updateFlowRunProcessData(["data" => ["outflow_process" =>$nextFlowProcess,"outflow_user" =>$processHostUser], "wheres" => ["run_id" => [$runId],"flow_process" => [$flowProcess], "host_flag" => ["1"]],"whereRaw" => ["(outflow_process = 0)"]]);
				}
				}
			}
		}
	}
    /**
     * 匹配依赖字段
     *
     * @param array $flowRunDatabaseData
     * @param array $flowInfo
     * @param string $dependentControlParentId
     * @param string $type 解析主数据依赖字段（main）或明细数据依赖字段（detail）
     * @return string,array
     */
    public function parseDataForDependentField($flowRunDatabaseData, $flowInfo, $dependentControlParentId = '', $type = 'detail')
    {
        // 依赖字段数组
        $dependentFieldArr = $flowInfo['outsend_has_many_dependent_fields'];
        $dependentFieldMatchRelations = [];

        $dependentFieldConfig = [];
        foreach ($dependentFieldArr as $dependentFieldValue) {
            if (($type == 'detail') && $dependentFieldValue['to_detail']) { // 明细数据依赖字段
                $dependentFieldConfig = $dependentFieldValue;
                break;
            }
            if (($type == 'main') && !$dependentFieldValue['to_detail']) { // 主数据数据依赖字段
                $dependentFieldConfig = $dependentFieldValue;
                break;
            }
        }

        // 依赖字段数组格式为 [依赖字段=>表单字段]数组
        foreach ($flowRunDatabaseData as $dataKey => $dataValue) {
            if ($dependentControlParentId) { // 依赖字段是明细字段
                if ($dependentControlParentId == $dataKey) {
                    foreach ($dataValue as $detailDataKey => $detailDataValue) {
                        if ($detailDataKey == $dependentFieldConfig['form_field']) {
                            $dependentFieldMatchRelations[$dependentFieldConfig['dependent_field']] = $detailDataValue;
                        }
                    }
                }
            } else { // 依赖字段是非明细字段
                if ($dataKey == $dependentFieldConfig['form_field']) {
                    $dependentFieldMatchRelations[$dependentFieldConfig['dependent_field']] = $dataValue;
                }
            }
        }
        return $dependentFieldMatchRelations;
    }

    /**
     * @DESC 流程外发新增和更新模式的依赖字段处理，将run_id匹配为unique_id
     *
     * @param [type] $dependentField
     * @param  $moduleMenu 当前外发的模块
     * @return $runId
     */
    public function handleDependentField($dependentField, $moduleMenu)
    {
        $dependentFieldsArr = [];
        $uniqueIdArr = [];
        // 如果是run_id则转换为unique_id
        if (isset($dependentField['run_id'])) {
            $runIdArr = is_array($dependentField['run_id']) ? $dependentField['run_id'] : array_unique(explode(',', trim($dependentField['run_id'], ',')));

            foreach ($runIdArr as $key => $runIdVal) {
                if (!$runIdVal || !is_numeric($runIdVal)) {
                    unset($runIdArr[$key]);
                    continue;
                }
                $uniqueIdArr = DB::table('flow_outsend_to_module_log')
                                ->where('flow_run_id', $runIdVal)
                                ->where('module_to', $moduleMenu)
                                ->where('data_handle_type', '!=', 2)
                                ->pluck('id_to')
                                ->toArray();

                $deletedUniqueRes = DB::table('flow_outsend_to_module_log')
                                ->where('flow_run_id', $runIdVal)
                                ->where('module_to', $moduleMenu)
                                ->where('data_handle_type', '=', 2)
                                ->pluck('id_to')
                                ->toArray();
                $uniqueIdArr = array_diff($uniqueIdArr, $deletedUniqueRes);
                if (is_string($dependentField['run_id'])) { // 非明细字段多插入一层，说明是同一个控件解析出来的数据
                    $dependentFieldsArr[0][$key]['run_id'] = $runIdVal;
                    $dependentFieldsArr[0][$key]['unique_id'] = $uniqueIdArr;
                } else { // 明细字段遍历保存，说明是不同行的控件数据解析出来的结果
                    $dependentFieldsArr[$key]['run_id'] = $runIdVal;
                    $dependentFieldsArr[$key]['unique_id'] = $uniqueIdArr;
                }
            }
        } else {
            $uniqueIdArr = is_array($dependentField['unique_id']) ? $dependentField['unique_id'] : array_unique(explode(',', trim($dependentField['unique_id'], ',')));
            if (!is_array($dependentField['unique_id'])) { // 非明细字段多插入一层，说明是同一个控件解析出来的数据
                $dependentFieldsArr[0][0]['unique_id'] = $uniqueIdArr;
            } else { // 明细字段unique_id拆分保存，说明是不同行的控件数据
                foreach ($uniqueIdArr as $uniqueIdKey => $uniqueIdValue) {
                    $dependentFieldsArr[$uniqueIdKey]['unique_id'] = $uniqueIdValue;
                }
            }

        }
        return ['dependentFieldsArr' => $dependentFieldsArr, 'uniqueIdArr' => array_unique($uniqueIdArr)];
    }

    /*
     * 格式化定时任务参数
     *
     * type 年月日周 循环周期
     * month
     * day
     * week
     * trigger_time 触发时间 时分秒
     * attention_content 提醒内容
     *
     * @since zyx 20200408
     */
    public function parseScheduleConfig($schedule_config) {
        $returnData = [];
        $returnData['type'] = $schedule_config['type'];
        $returnData['flow_id'] = (int)$schedule_config['flow_id'];
        if ($schedule_config['type'] == 'year') {
            $returnData['week'] = 1;
            $returnData['month'] = $schedule_config['month'] ? (int)$schedule_config['month'] : 1;
            $returnData['day'] = $schedule_config['day'] ? (int)$schedule_config['day'] : 1;
        } else if ($schedule_config['type'] == 'month') {
            $returnData['week'] = 1;
            $returnData['month'] = 1;
            $returnData['day'] = $schedule_config['day'] ? (int)$schedule_config['day'] : 1;
        } else if ($schedule_config['type'] == 'week') {
            $returnData['week'] = $schedule_config['week'] ? (int)$schedule_config['week'] : 1;
            $returnData['month'] = 1;
            $returnData['day'] = 1;
        } else if ($schedule_config['type'] == 'day') {
            $returnData['month'] = 1;
            $returnData['day'] = 1;
            $returnData['week'] = 1;
        }
        $returnData['trigger_time'] = date('H:i', strtotime($schedule_config['trigger_time']));
        $returnData['created_at'] = date('Y-m-d H:i:s');
        $returnData['attention_content'] = (isset($schedule_config['attention_content']) && $schedule_config['attention_content']) ? $schedule_config['attention_content'] : trans('flow.please_handle_current_flow'); // 默认提示内容
        return $returnData;
    }

    /**
     * 解析表单控件数据集
     * @return array
     */
    public function getControlTypeData()
    {
        $needControl = [
            'data-efb-border-type',
            'data-efb-format',
            'data-efb-source',
            'data-efb-source-value',
            'data-efb-options',
            'data-efb-selected-options',
            'data-efb-data-selector-category',
            'data-efb-data-selector-type',
            'data-efb-data-selector-mode',
            'data-efb-datetime-calculate',
            'data-efb-width',
            'data-efb-height',
            'data-efb-min-height',
            'data-efb-max-height',
            'data-efb-is-current',
            'data-efb-allow-set-style',
            'data-efb-allow-hide-user-name',
            'data-efb-allow-hide-department',
            'data-efb-allow-hide-user-img',
            'data-efb-allow-hide-time',
            'data-efb-use-default',
            'data-efb-cover-countersign',
            'data-efb-sort-by',
            'data-efb-order-by',
            'data-efb-image-width',
            'data-efb-image-height',
            'data-efb-data-selector-mode',
            'data-efb-orientation',
            'data-efb-decimal-places',
            'data-efb-decimal-places-digit',
            'data-efb-layout-max-rows',
            'data-efb-upload-method',
            'data-efb-only-image',
            'data-efb-file-count',
            'data-efb-queue-style',
            'data-efb-file-size',
            'data-efb-btn-text',
            'data-efb-amount-in-words',
            'data-efb-readonly',
            'data-efb-add-rows',
            'data-efb-init-rows',
            'data-efb-is-show-number',
            'data-efb-is-adaption',
            'data-efb-thousand-separator',
            'data-efb-title-align',
            'data-efb-display-form',
            'data-efb-selector-mode',
            'data-efb-multiple',
            'data-efb-allow-hide-userimg',
            'data-efb-allow-hide-username',
            'data-efb-rounding',
            'data-efb-validate-reg-exp',
            'data-efb-default',
            'data-efb-readonly',
            'data-efb-placeholder-type',
            'data-efb-placeholder',
            'data-efb-percentage',
            'data-efb-data-selector-default-value',
            'data-efb-data-selector-default',
            // 动态信息数据源
            'data-efb-dynamic-layout-info',
            'data-efb-layout-max-rows',
            'data-efb-conditions',
            'data-efb-data-selector-category',
            'data-efb-url',
            'data-efb-hide-file-name',
            'data-efb-layout-info',
            // 新增条码信息的解析
            'data-efb-encode-rule',
            'data-efb-encode-length',
            'data-efb-bar-code-type',
            'data-efb-encode-type',
            'data-efb-code-type',
            'data-efb-is-include-leave'
        ];
        $result = FlowFormControlStructureEntity::select('form_id', 'control_id', 'control_title', 'control_type', 'control_attribute')
            ->orderBy('sort', 'asc')
            ->whereNull('updated_at')
            ->whereNull('deleted_at')
            // ->groupBy('control_title')
            ->limit(500)
            ->get()
            ->toArray();

        $control = array_group_by($result, "control_title");
        $data = [];
        foreach ($control as $k => $v) {
            $data[$k]['type'] = current($v)['control_type'];
            $control_attribute = json_decode(current($v)['control_attribute'], true);
            $attribute = [];
            foreach ($control_attribute as $item => $value) {
                if (in_array($item, $needControl)) {
                    if (
                        ($value === '{"format":"day","formatDecimalPlace":null}') ||
                        ($value === '{"formatDecimalPlace":null}') ||
                        ($value === '{"formatDecimalPlace":null,"format":"day"}') ||
                        ($value === 'formula')
                    ) {
                        continue;
                    }

                    $attribute[$item] = $value;
                }
            }
            $data[$k]['attribute'] = $attribute;
        }
        return $data;
    }

    // 获得流程运行节点id
    public function getFlowRunProcessId($runId, $hostFlag = null, $userId = '', $flowProcess = '') {
    	$search = [];
    	$flowRunProcessId = 0;
    	$search["search"] = ["run_id" => [$runId]];
    	$search["order_by"] = ["flow_run_process_id" => "desc"];
    	if (!is_null($hostFlag)) {
    		$search["search"]["host_flag"] = [$hostFlag];
    	}
    	if (!empty($flowProcess)) {
    		$search["search"]["flow_process"] = [$flowProcess];
    	}
    	if (!empty($userId)) {
    		$search["search"]["user_id"] = [$userId];
    	}
    	$search["fields"] = ['flow_run_process_id'];
    	if ($flowRunProcessObject = app($this->flowRunProcessRepository)->getFlowRunProcessList($search)) {
    		$flowRunProcessObject = $flowRunProcessObject->toArray();
    		$flowRunProcessId = $flowRunProcessObject[0]['flow_run_process_id'];
    	}

    	return $flowRunProcessId;
    }

    // 生成并发分支的节点所属分支信息
    public function resetbranchInfo($flow_id , $flowProcessInfo = []) {
        if (empty($flowProcessInfo)) {
             $flowProcessInfo  = $this->getFlowProcessInfo($flow_id , false);
        }
        $concurrent = [];
        if (!empty($flowProcessInfo)) {
            $flowProcessInfo = array_column($flowProcessInfo , null ,'node_id');
            // 普通流程没有并发节点可以直接返回显示
            foreach ($flowProcessInfo as $k1 => $v1) {
                if (!empty($v1['concurrent'])) {
                    array_push( $concurrent, $k1);
                }
            }
        }
        $merge_node = 0;
        app($this->flowProcessRepository)->updateData(['branch'=> 0 ,'origin_node' => 0 , 'merge_node' =>$merge_node , 'line_to_merge_node' => 0  ], ["flow_id" => [$flow_id]]);
        if (empty($concurrent)) {
            // 如果没有并发节点，直接将合并节点清空，单纯的合并节点没有意义
            app($this->flowProcessRepository)->updateData(['merge'=> 0 ], ["flow_id" => [$flow_id]]);
            return true; // 如果是没有并发节点直接结束
        }
        $i = 1;
        // 遍历节点信息获取并发分支树,concurrent的数量表示有几个并发节点
        foreach ($concurrent as $k2 => $v2) {
            $process_to =  $flowProcessInfo[$v2]['process_to'];
            if (empty( $process_to)) {
                continue;
            }
            $process_to = explode(',', $process_to);
            $all = [$v2];
            $merge_node = 0;

            // 此时可能会形成多条分支,每一条分支进行递归查询
            foreach ($process_to as $pk => $pv) {
                if ($flowProcessInfo[$pv]['sort'] < $flowProcessInfo[$v2]['sort']) {
                    continue;
                }
               $n = $this->recursivelyGetBranch( $flowProcessInfo , $pv , true );
               if (empty($merge_node)) {
                   $merge_node = $n['merge_node'];
               }
               $line_to_merge_node = 0;
               if ($n['merge_node']) {
                    $line_to_merge_node = 1;
               }
               $n = array_merge([$pv] , $n['nodes']);
               $all = array_merge($all ,   $n);
               app($this->flowProcessRepository)->updateData(['branch'=> $i , 'origin_node' => $v2 , 'line_to_merge_node' => $line_to_merge_node ], ["node_id" => [  $n , 'in']]);
               $i++;
            }
            $updateDataArr = [];
            $updateDataArr['end_workflow'] = 0;
            if ($merge_node) {
                $updateDataArr['merge_node'] = $merge_node;
            }
            // 此时更新这个并发节点的可以合并的节点,并且将并发节点和分支上的节点强制更改是否可以结束流程
            app($this->flowProcessRepository)->updateData($updateDataArr, ["node_id" => [  $all , 'in']]);
        }
    }



    public function recursivelyGetBranch($flowProcessInfo , $node_id , $clear = false) {
        static $nodes = [];
        static $hasRecursively = []; // 已经遍历过的节点
        static $merge_node = 0;
        static $branches = [];
        if ($clear) {
            $nodes = [];
            $merge_node = 0;
        }
        $process_to = $flowProcessInfo[$node_id]['process_to'];
        if (!empty( $process_to)) {
            $process_to = explode(',', $process_to);
            foreach ($process_to as $k1 => $v1) {
                // 如果这个节点已经被遍历
                if (in_array($v1, $hasRecursively)) {
                     continue;
                }
                if ($flowProcessInfo[$v1]['branch']) {
                     array_push($branches, $v1);
                }
                // 不是并发节点，不是合并节点 ，过滤退回节点
                if (empty($flowProcessInfo[$v1]['concurrent']) && empty($flowProcessInfo[$v1]['merge']) ) {
                    if ($flowProcessInfo[$v1]['sort'] > $flowProcessInfo[$node_id]['sort']) {
                         array_push($nodes, $v1);
                         array_push($hasRecursively, $v1);
                    } else {
                        continue;
                    }
                } else {
                    // 保存并发节点信息
                    if (!empty($flowProcessInfo[$v1]['merge']) && $flowProcessInfo[$v1]['sort'] > $flowProcessInfo[$node_id]['sort']) {
                         $merge_node = $v1;
                         // array_push($hasRecursively, $v1);
                    }
                    // 此时就无需向下判断了
                    continue;
                }
                // 递归下一个节点
                $this->recursivelyGetBranch($flowProcessInfo ,  $v1);
            }

        }
        return [ 'nodes' =>$nodes , 'merge_node' =>$merge_node , 'branches' => $branches];
    }

    // 验证分支节点 , 设置保存时需要验证
    public function recursivelyVerifyBranch($flowProcessInfo , $node_id , $origin ,$clear = false ,$set_merger_node = 0 ) {
        static $verifyNodes = []; // 验证节点
        static $concurrent = []; // 并发节点
        static $hasVerifyRecursively = []; // 已经遍历过的节点
        static $forwardToMerge = []; // 一条分支上流向正向合并节点的节点
        static $forwardToMany = []; // 分支上的节点设有多个出口
        if ($clear) {
            $verifyNodes = [];
            $hasVerifyRecursively = [];
            $forwardToMerge = [];
            $forwardToMany = [];
        }
        $process_to = $flowProcessInfo[$node_id]['process_to'];
        if (!empty( $process_to)) {
            $process_to = array_filter(explode(',', $process_to));
            if (count($process_to) > 1) {
                $i = 0;
                foreach ($process_to as $k2 => $v2) {
                    if ($flowProcessInfo[$v2]['sort'] > $flowProcessInfo[$node_id]['sort']) {
                        $i++;
                    }
                 }
                 if ($i>=2) {
                     array_push($forwardToMany, $node_id);
                 }
            }
            foreach ($process_to as $k1 => $v1) {
                // 如果这个节点已经被遍历
                if (in_array($v1, $hasVerifyRecursively) || $v1 == $origin) {
                     continue;
                }
                // 如果此时是设置合并节点
                if ($set_merger_node &&  $v1 == $set_merger_node) {
                    if ($flowProcessInfo[$v1]['sort'] > $flowProcessInfo[$node_id]['sort']) {
                        array_push($forwardToMerge, $node_id);
                    }
                    continue;
                }
                // 不是并发节点
                if (empty($flowProcessInfo[$v1]['concurrent']) ) {
                     array_push($verifyNodes, $v1);
                     array_push($hasVerifyRecursively, $v1);
                }
                if ($flowProcessInfo[$v1]['concurrent']) {
                    array_push($concurrent, $v1);
                    array_push($hasVerifyRecursively, $v1);
                    continue;
                }
                // 递归下一个节点
                $this->recursivelyVerifyBranch($flowProcessInfo ,  $v1 , $origin , false  , $set_merger_node);
            }

        }
        return ['verifyNodes' =>$verifyNodes , 'concurrent' => $concurrent , 'mergeNode' =>$set_merger_node , 'forwardToMerge' => $forwardToMerge , 'forwardToMany' => $forwardToMany];
    }


    /**
     * 判断监控人在并发流程下提交是选择步骤去办理还是直接办理
     * @param $runId
     * @return bool
     */
    public function needChooseProcessToDo($runId)
    {
        // 当前流程数据 按分支和 process_id 分组，取未办理的数据，即分支上最新的步骤是否为待办
        $result = app($this->flowRunProcessRepository)->entity->where('run_id', $runId)->where('branch_serial', '!=', 0)->where('user_run_type', 1)->orderBy('process_id', 'DESC')->get()->toArray();
        if (count($result)) {
            $data = array_group_by($result, 'branch_serial'); // 手动分组
            $maxProcessId = [];
            foreach ($data as $k => $v) {
                array_push($maxProcessId, collect($v)->max('process_id'));// 记录手动分组后的每组的最大步骤
            }
            $maxProcessId = array_values(array_unique($maxProcessId));
            $groups = [];
            foreach ($result as $value) {
                if (in_array($value['process_id'], $maxProcessId)) {
                    $groups[] = $value;
                }
            }
            if (count($groups)) {
//            if (count(array_group_by($groups, 'flow_process')) == 1) {
//                return false; // 说明都到达了合并节点，不用弹框选择
//            }
                return true;
            }
            return false;
        }
        return false;

    }

    // 获取某个合并节点之后的节点，主要用于并发节点已经流向合并节点之后，不能再流向合并节点之后的所有节点
    public function nodesAfterMerging($flowProcessInfo , $node_id , $clear = false) {
        static $nodes = [];
        static $hasRecursively = []; // 已经遍历过的节点
        if ($clear) {
            $nodes = [];
            $hasRecursively = [];
        }
        $process_to = $flowProcessInfo[$node_id]['process_to'];
        if (!empty( $process_to)) {
            $process_to = explode(',', $process_to);
            foreach ($process_to as $k1 => $v1) {
                // 如果这个节点已经被遍历
                if (in_array($v1, $hasRecursively)) {
                     continue;
                }
                if ($flowProcessInfo[$v1]['sort'] > $flowProcessInfo[$node_id]['sort']) {
                         array_push($nodes, $v1);
                         array_push($hasRecursively, $v1);
                } else {
                        continue;
                }
                // 递归下一个节点
                $this->nodesAfterMerging($flowProcessInfo ,  $v1);
            }
        }
        return $nodes;
    }

    /**
     * 使用flow_run_process_list，按照筛选条件找出符合符合条件的办理人
     *
     * @param [array] $flowRunProcessList
     * @param [array] $validations 筛选条件
     *
     * @author zyx
     * @since 20201027
     *
     * @return [array]
     */
    public function getStepHandlersBasedOnFlowRunProcessList($flowRunProcessList, $validations) {
        $handlerRes = ["handle_user_info_arr" => [], "handle_user_name_str" => "", "handle_user_id_arr" => []];

        // 按照筛选条件，将不符合条件的数据清除
        foreach ($flowRunProcessList as $key => $value) {
            // 指定节点
            if (
                isset($validations["process_id"]) &&
                ($validations["process_id"] != $value["process_id"])
            ) {
                unset($flowRunProcessList[$key]);
                continue;
            }
            // 办理状态
            if (
                isset($validations["user_run_type"]) &&
                ($validations["user_run_type"] != $value["user_run_type"])
            ) {
                unset($flowRunProcessList[$key]);
                continue;
            }
            // 用户流程特征
            if (
                isset($validations["user_last_step_flag"]) &&
                ($validations["user_last_step_flag"] != $value["user_last_step_flag"])
            ) {
                unset($flowRunProcessList[$key]);
                continue;
            }
            // flow_serial过滤
            if (
                isset($validations["flow_serial"]) &&
                ($validations["flow_serial"] != $value["flow_serial"])
            ) {
                unset($flowRunProcessList[$key]);
                continue;
            }
        }

        // 没有符合条件的节点直接返回
        if (!$flowRunProcessList) {
            return $handlerRes;
        }

        $i = 0;

        foreach ($flowRunProcessList as $flowRunProcess) {
            if ( empty($flowRunProcess["flow_run_process_has_one_user"])) {
                continue;
            }
            $userId = $flowRunProcess["flow_run_process_has_one_user"]["user_id"];
            if (empty($userStatus = $flowRunProcess["flow_run_process_has_one_user"]['user_has_one_system_info'])) {
                $userStatus = 0;
            } else {
                $userStatus = $flowRunProcess["flow_run_process_has_one_user"]['user_has_one_system_info']['user_status'];
            }

            // 已记录的用户直接跳过
            if (in_array($userId, $handlerRes["handle_user_id_arr"])) {
                continue;
            }

            $handlerRes["handle_user_id_arr"][] = $userId; // 用户ID数组
            $handlerRes["handle_user_info_arr"][$i][] = $userId; // 用户信息数组-用户ID
            $handlerRes["handle_user_info_arr"][$i][] = $flowRunProcess["flow_run_process_has_one_user"]["user_name"]; // 用户信息数组-用户名称
            $handlerRes["handle_user_info_arr"][$i][] = $userStatus; // 用户信息数组-用户状态

            // 用户名称字符串，拼接用户状态
            $tmp = $flowRunProcess["flow_run_process_has_one_user"]["user_name"];
            if (!$userStatus) { // 用户删除
                $tmp = $flowRunProcess["flow_run_process_has_one_user"]["user_name"] . '[' . trans('flow.delete') . ']';
            } else if ($userStatus == 2) { // 用户离职
                $tmp = $flowRunProcess["flow_run_process_has_one_user"]["user_name"] . '[' . trans('flow.leave_office') . ']';
            }
            $handlerRes["handle_user_name_str"] .=  $tmp . ',';

            $i++;
        }

        $handlerRes["handle_user_name_str"] = trim($handlerRes["handle_user_name_str"], ',');
        // 用户名称字符串过长时截断
        if (mb_strlen($handlerRes["handle_user_name_str"]) > 100) {
            $tmpStr = mb_substr($handlerRes["handle_user_name_str"], 0, 100);
            $handlerRes["handle_user_name_str"] = mb_substr($tmpStr, 0, strrpos($tmpStr, ',')) . '...' . trans('flow.more_unhandle_user_tip');
        }

        return $handlerRes;
    }

    /**
     * 当合并节点收回时，获取所有分支上的合并节点最大的process_id
     *
     * @param [array] $flowRunProcessList
     * @param [array] $currentFlowRunProcessInfo
     *
     * @author zyx
     * @since 20201102
     *
     * @return array
     */
    public function getProcessIdsOnMergeSteps($flowRunProcessList, $currentFlowRunProcessInfo) {
        $processIdArrGroupByFlowBranchProcessSerial = [];
        foreach ($flowRunProcessList as $value) {
            // 当前步骤，同一定义节点
            if (
                ($value['flow_serial'] == $currentFlowRunProcessInfo['flow_serial']) &&
                ($value['flow_process'] == $currentFlowRunProcessInfo['flow_process']) &&
                !isset($processIdArrGroupByFlowBranchProcessSerial[$value['flow_serial'] . '_' . $value['branch_serial'] . '_' . $value['process_serial']])
            ) {
                $i = 0;
                // 如果某个分支上，同一定义节点不是分支最后节点，或者尚未提交，则不能同步收回该分支上的该合并节点
                foreach ($flowRunProcessList as $innerValue) {
                    if (
                        ($innerValue['flow_serial'] == $value['flow_serial']) &&
                        ($innerValue['branch_serial'] == $value['branch_serial']) &&
                        (
                            ($innerValue['process_serial'] > $value['process_serial']) || // 合并节点不是分支最后步骤节点
                            ($value['process_flag'] < 3) // 合并节点尚未提交
                        )
                    ) {
                        $i++;
                        break;
                    }
                }
                // 如果合并节点是强制的，则提交时肯定是所有分支同步提交，合并节点肯定是各分支上的最后节点，可以同步收回
                // 如果合并节点是非强制的，则提交时会把每个分支上最后一步是该合并节点的分支同步提交，记录这个分支，进而同步收回
                if (!$i) {
                    $processIdArrGroupByFlowBranchProcessSerial[$value['flow_serial'] . '_' . $value['branch_serial'] . '_' . $value['process_serial']] = $value['process_id'];
                }
            }
        }

        return array_values($processIdArrGroupByFlowBranchProcessSerial);
    }

    /* 匹配外发数据创建人
     *
     * @param [array] $flowRunDatabaseData 表单数据
     * @param [string] $dataCreatorField 外发数据创建人配置，表单控件+流程创建人、节点提交人
     * @param [array] $param
     *
     * @author zyx
     * @return void
     */
    public function parseDataForDataCreatorField($flowRunDatabaseData, $dataCreatorField, $param) {
        $dataCreator = '';
        // 流程创建人、节点提交人
        if (in_array($dataCreatorField, ['flow_creator', 'flow_submit_user', ''])) {
            return app($this->flowOutsendService)->handleModuleFields($dataCreatorField, $flowRunDatabaseData, $param);
        }
        // 遍历表单字段，获取创建人对应控件的数据
        foreach ($flowRunDatabaseData as $dataKey => $dataValue) {
            if ($dataKey == $dataCreatorField) {
                $dataCreator = $dataValue;
                break;
            }
        }

        return trim($dataCreator, ',');
    }

    /**
     * 获取用户所在的所有步骤节点的来源节点，供更新来源节点状态使用
     *
     * @param $flowRunProcessList
     * @param $userId
     * @author zyx
     * @since 20201127
     */
    public function getOriginProcessIdsWithUserId($flowRunProcessList, $userId) {
        $originProcessIdsArr = [];
        foreach ($flowRunProcessList as $v) {
            if (
                ($v['user_id'] == $userId) && // 用户所在节点
                ($v['process_flag'] == 1) // 用户尚未查看
            ) {
                $originProcessIdsArr[] = $v['origin_process_id'];
            }
        }

        return array_unique($originProcessIdsArr);
    }

    /**
     * 格式化返回flow_others配置信息供流程使用
     *
     * @param [type] $flowOthersInfo
     * @return array
     */
    public function parseFlowOthersSetting($flowOthersInfo) {
        $returnData = [];

        // $returnData["lableShowDefault"] = $flowOthersInfo["lable_show_default"]; // handle view
        $returnData["flowAutosave"] = $flowOthersInfo["flow_autosave"];
        $returnData["feedBackAfterFlowEnd"] = $flowOthersInfo["feed_back_after_flow_end"];
        $returnData["flowShowName"] = $flowOthersInfo["flow_show_name"];
        $returnData["alowSelectHandle"] = $flowOthersInfo["alow_select_handle"];
        $returnData["flowDetailPageChoiceOtherTabs"] = $flowOthersInfo["flow_detail_page_choice_other_tabs"];
        $returnData["flowSendBackRequired"] = $flowOthersInfo["flow_send_back_required"];
        $returnData["flowSendBackSubmitMethod"] = $flowOthersInfo["flow_send_back_submit_method"];
        $returnData["flowSubmitHandRemindToggle"] = $flowOthersInfo["flow_submit_hand_remind_toggle"];
        $returnData["flowSubmitHandOvertimeToggle"] = $flowOthersInfo["flow_submit_hand_overtime_toggle"];
        $returnData["flow_to_doc"] = $flowOthersInfo["flow_to_doc"];
        $returnData["file_folder_id"] = $flowOthersInfo["file_folder_id"];
        $returnData["submitWithoutDialogSet"] = $flowOthersInfo["submit_without_dialog"];
        $returnData["backVerifyCondition"] = $flowOthersInfo["flow_send_back_verify_condition"] ?? 1;
        $returnData["noPrintUntilFlowEnd"] = $flowOthersInfo["no_print_until_flow_end"]; // 流程结束才能打印

        $returnData["firstNodeDeleteFlow"] = $flowOthersInfo["first_node_delete_flow"]; // handle
        $returnData["form_control_filter"] = $flowOthersInfo["form_control_filter"] ?? 0; // handle

        $returnData["forward_after_flow_end"] = $flowOthersInfo["forward_after_flow_end"]; //veiw
        $returnData["flowRunPrintTimesLimit"] = $flowOthersInfo["flow_print_times_limit"]; // view

        $returnData["flowShowHistory"] = $flowOthersInfo["flow_show_history"] ?? 1; //new
        $returnData["flowShowDataTemplate"] = $flowOthersInfo["flow_show_data_template"] ?? 0; //new
        $returnData["flowShowUserTemplate"] = $flowOthersInfo["flow_show_user_template"] ?? 0; //new
        $returnData["flowPrintTemplateMode"] = $flowOthersInfo["flow_print_template_mode"] ?? 1;

        return $returnData;
    }

    /**
     * 判断用户对当前流程的办理权限，如果有超过2个以上的可办理数据则直接返回特征值
     */
    public function checkFlowRunHandleAuthority($flowRunProcessList, $userInfo) {
        $canHandle = 0;
        $flowRunProcessInfoToHandle = [];

        foreach ($flowRunProcessList as $value_process) {
            // 超过2个可办理数据则直接返回特征值
            if ($canHandle > 1) {
                break;
            }

            // 判断是否有当前人员可以办理的运行节点
            if (
                ($value_process['user_id'] == $userInfo['user_id']) &&
                ($value_process['user_run_type'] == 1  && $value_process['user_last_step_flag'])
            ) {
                $flowRunProcessInfoToHandle[$canHandle]['flow_id'] = $value_process['flow_id'];
                $flowRunProcessInfoToHandle[$canHandle]['flow_run_process_id'] = $value_process['flow_run_process_id'];
                $flowRunProcessInfoToHandle[$canHandle]['process_id'] = $value_process['process_id'];
                $flowRunProcessInfoToHandle[$canHandle]['flow_process'] = $value_process['flow_process'];

                $canHandle++;
            }
        }

        return ['canHandle' => $canHandle, 'flowRunProcessInfoToHandle' => $flowRunProcessInfoToHandle];
    }


    /**
     *  获取流程委托和交办的人员，用于获取办理人员过滤
     *
     * @param $flowRunProcessList
     * @param $userId
     * @author wz
     * @since
     */
    public function getAgentAndReplaceUserList($flowRunProcessIds) {
        if (empty($flowRunProcessIds) || !is_array($flowRunProcessIds)) {
            return [];
        }
       return app($this->flowRunProcessAgencyDetailRepository)->entity->whereIn("flow_run_process_id",$flowRunProcessIds)->pluck("by_agency_id")->toArray();
    }

    /**
     * 更新用户user_last_step_flag参数
     *
     * @param $runId
     * @param $userArrToUpdate 需要更新数据的用户ID
     *
     * @author zyx
     *
     */
    public function updateUserLastStepsFlag($runId, $userArrToUpdate = []) {
        $flowRunProcessParam = [
                "run_id" => $runId,
                "order_by" => [ 'user_run_type' => 'asc' , 'host_flag' => 'desc' , 'process_id' => 'desc' ],
        ];
        $flowRunProcessList = app($this->flowRunProcessRepository)->getFlowRunProcessList($flowRunProcessParam)->toArray();
        // 1.流程运行数据按用户分组
        $FBPListGroupByUser = array_group_by($flowRunProcessList, 'user_id');
        // 按分支分组，后边调用
        $AllFBPListGroupByUserBranch = array_group_by($flowRunProcessList, 'branch_serial');
        $updateFBPArr = [];
        // 2.遍历分组后的数组
        foreach ($FBPListGroupByUser as $userValue) {
            // 如果不是要处理的用户则跳过
            if ($userArrToUpdate && !in_array($userValue[0]['user_id'], $userArrToUpdate)) {
                continue;
            }

            // 3.用户数据按分支分组
            // 普通流程不同节点在一个单元
            // 不同分支在不同单元
            $FBPListGroupByUserBranch = array_group_by($userValue, 'branch_serial');

            // 按分支分组的个数
            $countOfBranchArr = count($FBPListGroupByUserBranch);

            // 只有一个单元，说明:
            // 0.1用户都在普通节点
            // 0.2用户只在某一个分支
            if ($countOfBranchArr == 1) {
                foreach ($FBPListGroupByUserBranch as $userBranchValue) {
                    // 调用一次获取要更新的数据即跳出
                    $branchValue = $this->getUpdateFBPInfoAfterGroupBy($userBranchValue);
                    if (isset($branchValue)) {
                        $updateFBPArr[] = $branchValue;
                    }
                    break;
                }

                continue;
            }

            // 超过一个单元，说明:
            // 0.1用户至少在2个分支
            // 0.2用户至少在1个普通节点，且至少在1个分支
            // $maxFlowSerialOnEachBranch = [];
            // foreach ($FBPListGroupByUserBranch as $userBranchValue) {
            //     // 找出每个分支单元最大的flow_serial
            //     $maxFlowSerialOnEachBranch[$userBranchValue['branch_serial']] = collect($userBranchValue)->max('flow_serial');
            // }

            // flow_serial去重
            // $countUniqMaxFlowSerial = count(array_unique($maxFlowSerialOnEachBranch));
            // 去重后flow_serial只有一个，说明用户是在同一步骤flow_serial的不同分支
            // 去重后flow_serial不只一个，说明有分支上的也有普通节点的上的

            // 遍历分支分组，获取每个分组上要更新的数据
            foreach ($FBPListGroupByUserBranch as $userBranchValue) {
                $branchValue = $this->getUpdateFBPInfoAfterGroupBy($userBranchValue);
                if ($branchValue) {
                    $updateFBPArr[] = $branchValue;
                }
            }
        }
        // 遍历要更新的数据
        foreach ($updateFBPArr as $updateFBP) {
            app($this->flowRunProcessRepository)->updateData(['user_last_step_flag' => 1], ['flow_run_process_id' => [$updateFBP['flow_run_process_id']]]);
            // 遍历分支，确保只有一个user_last_step_flag=1
            $userBranchInfo = $updateFBP['branch_serial'];

            if (isset($AllFBPListGroupByUserBranch[$userBranchInfo]) && $AllFBPListGroupByUserBranch[$userBranchInfo]) {
                foreach ($AllFBPListGroupByUserBranch[$userBranchInfo] as $_value) {
                    // 逐个更新当前user_id 在当前分支上 user_last_step_flag不应该为1的数据
                    if($_value['flow_run_process_id'] != $updateFBP['flow_run_process_id'] && $_value['user_id'] == $updateFBP['user_id'] ) {
                        app($this->flowRunProcessRepository)->updateData(['user_last_step_flag' => 0], ['flow_run_process_id' => [$_value['flow_run_process_id']]]);
                    }
                }
            }
        }
        return true;
    }

    /**
     * 获取用户-分支分组后，每个分组上用户所在最新flow_run_process数据
     */
    public function getUpdateFBPInfoAfterGroupBy($userBranchValue) {
        // 取当前分支分组上用户所在步骤process_id最大的步骤
        $maxProcessId = collect($userBranchValue)->max('process_id');
        $handleInfo = [];
        foreach ($userBranchValue as $FBPInfo) {
            if ($FBPInfo['process_id'] == $maxProcessId) {
                // 最新步骤，主办人条目优先级高于经办人条目，直接记录并跳出
                return $FBPInfo;
            }else {// 不在最新步骤时，未办理时以经办人为主,都办理了，以主办人为主
                if($FBPInfo['user_run_type'] == 1) {
                    return $FBPInfo;
                }else{
                    if($FBPInfo['host_flag'] == 1) {
                        return $FBPInfo;
                    }else {
                        // 记录已办理的经办人信息
                        $handleInfo[] = $FBPInfo;
                    }
                }
            }
        }
        if(count($handleInfo)) {
            return $handleInfo[0];
        }
    }

    /*
     * 获取用户ID和状态，拼接后返回
     *
     * @author zyx
     * @since 20201119
     *
     * @return string
     */
    public function getUserSimpleInfo($userId) {
        $userInfo = DB::table('user')
            ->leftJoin('user_system_info as usi', 'usi.user_id', '=', 'user.user_id')
            ->select('user.user_id', 'user.user_name', 'usi.user_status')
            ->where('user.user_id', $userId)
            ->get()
            ->toArray();

        if (!$userInfo) {
            return '';
        }

        // 用户状态数组
        $userStatusArr = [0 => '[' . trans('flow.deletion') . ']', 1 => '', 2 => '[' . trans('flow.resignation') . ']'];

        $user = json_decode(json_encode(($userInfo[0])), true);
        // 拼接用户状态返回
        return $user['user_name'] . (in_array($user['user_status'], [0, 2]) ? ($userStatusArr[$user['user_status']]) : '');
    }

    /**
     * 修改明细二级子项控件结构和部分参数，用于流程外发时获取流程表单数据
     *
     * @param [type] $formControlStructure
     * @return array
     */
    public function handleDetailLayoutGrandchildrenStructure($formControlStructure) {
        foreach ($formControlStructure as $controlKey => $controlValue) {
            $controlValueAttr = json_decode($controlValue['control_attribute'], true);
            // 明细二级子项的父级ID改为祖父级ID
            if (
                isset($controlValueAttr['pItemType']) &&
                ($controlValueAttr['pItemType'] == 'column')
            ) {
                if (isset($controlValueAttr['control_grandparent_id'])) {
                    $newParentId = $controlValueAttr['control_grandparent_id'];
                } else {
                    foreach ($formControlStructure as $tmpValue) {
                        if ($tmpValue['control_id'] == $controlValueAttr['control_parent_id']) {
                            $formControlStructure[$controlKey]['origin_control_parent_id'] = $controlValue['control_parent_id'];
                            $newParentId = $tmpValue['control_parent_id'];
                            break;
                        }
                    }
                }

                $formControlStructure[$controlKey]['control_parent_id'] = $newParentId;
            }
        }

        return $formControlStructure;
    }

    /**
     * 重新组装structure，用于getFlowFormParseData方法
     *
     * @param [type] $formControlStructure
     * @return array
     */
    public function handleFlowFormStructure($formControlStructure) {
        foreach ($formControlStructure as $controlStructureKey => $controlStructureValue) {
            $controlType = $controlStructureValue["control_type"] ?? "";
            if(isset($controlStructureValue["control_attribute"])) {
                $controlStructureAttribute = json_decode($controlStructureValue["control_attribute"], true);
            }
            if (($controlType != "detail-layout" && isset($controlStructureValue["control_attribute"]) && $controlStructureValue["control_attribute"])) {
                // 明细二级子项需要补充祖父ID参数
                if (isset($controlStructureAttribute['pItemType'])) {
                    if (!isset($controlStructureAttribute["control_grandparent_id"])) {
                        $controlStructureAttribute["control_grandparent_id"] = '';
                        // 某些情况下没有将祖父ID存入，需要处理
                        foreach ($formControlStructure as $tmpControlValue) {
                            if ($tmpControlValue['control_id'] == $controlStructureValue['control_parent_id']) {
                                $controlStructureAttribute["control_grandparent_id"] = $tmpControlValue['control_parent_id'];
                                break;
                            }
                        }
                    }

                    $formControlStructure[$controlStructureKey]["control_grandparent_id"] = $controlStructureValue["control_grandparent_id"] = $controlStructureAttribute["control_grandparent_id"];
                }

                // 有_TEXT属性要补充一个_TEXT单元
                $dataEfbWithText = $controlStructureAttribute["data-efb-with-text"] ?? "";
                if ($dataEfbWithText) {
                    // 记录原型的control_id
                    $controlStructureValue["control_prototype"] = $controlStructureValue["control_id"];
                    $controlStructureValue["control_id"] = $controlStructureValue["control_id"] . "_TEXT";
                    $controlStructureValue["control_type"] = $controlType . "-text";
                    $formControlStructure[] = $controlStructureValue;
                }

                // 明细二级子项
                if (
                    ($controlType == 'column') &&
                    isset($controlStructureAttribute['children'])
                ) {
                    $childrenControlStructure = is_array($controlStructureAttribute['children']) ? $controlStructureAttribute['children'] : json_decode($controlStructureAttribute['children'], true);
                    if (!$childrenControlStructure) {
                        continue;
                    }

                    // 遍历明细二级数组
                    foreach ($childrenControlStructure as $childControlStructure) {
                        if (empty($childControlStructure['attribute']) || empty($childControlStructure['attribute']['id'])) {
                            continue;
                        }
                        $childControlAttr = $childControlStructure['attribute'];
                        $childControlStructure['control_attribute'] = json_encode($childControlStructure['attribute']);

                        $childControlStructure["control_id"] = $childControlAttr["id"];
                        $childControlStructure["control_type"] = $childControlAttr["type"];
                        $childControlStructure["control_parent_id"] = $controlStructureValue["control_id"]; // 明细二级子项的父级
                        $childControlStructure["control_grandparent_id"] = $controlStructureValue["control_parent_id"]; // 明细二级子项的祖父级
                        $childControlStructure["control_title"] = $childControlAttr["title"];

                        // 有_TEXT属性要补充一个_TEXT单元
                        $childDataEfbWithText = $childControlAttr["data-efb-with-text"] ?? "";
                        if ($childDataEfbWithText) {
                            // 记录原型的control_id
                            $childControlStructure["control_prototype"] = $childControlStructure["control_id"];
                            $childControlStructure["control_id"] = $childControlStructure["control_id"] . "_TEXT";
                            $childControlStructure["control_type"] = $childControlStructure["control_type"] . "-text";
                        }

                        $formControlStructure[] = $childControlStructure;
                    }
                }
            }
            if ($controlType == "detail-layout" && isset($controlStructureAttribute['pItemType'])) {
                // 明细二级子项需要补充祖父ID参数
                if (isset($controlStructureAttribute['pItemType'])) {
                    $formControlStructure[$controlStructureKey]["control_grandparent_id"] = $controlStructureAttribute["control_grandparent_id"];
                }
            }
        }

        return $formControlStructure;
    }

    /*
     * 合并节点是否已退回到某些分支节点，主要是为了 合并节点退回时要控制不能重复退回
     */
    public function mergeHasBackTobranch($flowRunProcess , $mergeProcess) {
        $branchGroupData  = array_group_by($flowRunProcess, 'branch_serial'); // 按分支分组。 key值为branch_serial
        $canBack = [];
        foreach ($branchGroupData as $k1 => $v1) {
            $maxProcessId = collect($v1)->max('process_id'); // 每个分支最大步骤
            // 遍历每个分支，找出每个分支上的最大步骤
            foreach ($v1 as $k2 => $v2) {
               if ( $maxProcessId  == $v2['process_id'] && $v2['flow_process'] ==  $mergeProcess) {
                    // 最大步骤这个是合并节点，说明这个节点所处的 分支到了合并节点啊
                    array_push( $canBack, $v2['origin_process']);
               }
            }
        }
        return $canBack;
    }

    // 流程流转后，标记某些用户历史的此条流程的未读消息为已读
    public function markUnreadMessagesAsRead($flowId, $runId, $userId)
    {
        if (empty($flowId) || empty($runId) || empty($userId)) {
            return true;
        }
        if (!is_array($userId)) {
            $userId = [$userId];
        }
        $smsParam = [
            'search' => [
                'system_sms.sms_menu' => ['flow'],
                'system_sms.sms_type' => [['submit', 'back', 'entrust', 'monitor', 'forward', 'overtime', 'overtimeSubmit', 'autoSubmit', 'assigned', 'urge', 'press', 'concourse'], 'in'],
                'system_sms_receive.recipients' => [$userId, 'in'],
                // 这里用这个like查询可能会查到错误数据，比如同一个流程里我有run_id=100和run_id=1000的都是未读，此时更新100的时候就会把1000的也更新掉，由于数据结构的原因，暂时只能做到这样，由于加了flowId的查询条件这种情况基本也很少出现，multiSearch也不适用于一个字段的同时or+like查询，后续数据结构改进了再统一做改进
                'system_sms.params' => ['{"flow_id":'.$flowId.',"run_id":'.$runId.'%' , 'like'],
            ]
        ];
        // 调用消息已读
        return app($this->systemSmsService)->moduleToReadSms($smsParam, $userId);
    }

    /**
     *  获取最初的来源转发人或者委托人， 主要用于表单节点模版获取最初那个人的模版规则
        wz
     */
    public function getOriginUserIdByForword( $runId , $processId ,  $user_id , $dataList = []) {
        static $last_user;
        static $flow_run_process_id = [];
        if  (empty($dataList)) {
            $flowRunProcessParam = [
                        "run_id" => $runId,
                        "search" => [ "process_id" => [$processId] ],
                        "order_by" => ['host_flag' => 'desc', 'forward_user_id' => 'asc', 'by_agent_id' => 'asc'],
                    ];
            $dataList = app($this->flowRunProcessRepository)->getFlowRunProcessList($flowRunProcessParam)->toArray();
        }
        foreach ($dataList as $k => $v) {
            $originUser = empty($v['forward_user_id']) ? $v['by_agent_id'] : $v['forward_user_id'];
            if (in_array($v['flow_run_process_id'], $flow_run_process_id)) {
                break;
            }
            if ($v['user_id'] == $user_id && $originUser ) {
                array_push($flow_run_process_id, $v['flow_run_process_id']);
                $last_user = $originUser;
                // 此时说明该用户还是别人转发或者委托的
                return $this->getOriginUserIdByForword( $runId , $processId, $originUser , $dataList);
            }
        }
        return $last_user;

    }
        /**
     * 在分支上的节点是否可以结束流程
     * @param $flowId
     * @param $runId
     * @param $processId 流程步骤
     * @return bool
     */
    public function isCanFinishedOnBranch($flowId, $runId, $processId , $flowProcess = 0)
    {
        // 获取所有的节点信息
        $flowProcessInfo = $this->getFlowProcessInfo($flowId);
        $flowProcessnodeKeyInfo = array_column($flowProcessInfo , null ,'node_id');
        if ($flowProcess && !$flowProcessnodeKeyInfo[$flowProcess]['branch']) {
            return true; // 不是分支上节点就直接返回 可以结束了
        }
        $flowRunSerialInfo = app($this->flowRunProcessRepository)->getFlowRunProcessList([
            'search' => ['run_id' => [$runId], 'process_id' => [$processId]],
            'returntype' => 'first',
            "fields" =>  ['concurrent_node_id' , 'flow_serial'] // 用于判断是否从合并节点并发而来
        ]);
        if ($flowRunSerialInfo && $flowRunSerialInfo->concurrent_node_id) {
             $flowRunSameFlowSerial = app($this->flowRunProcessRepository)->getFlowRunProcessList([
                    'search' => ['run_id' => [$runId], 'concurrent_node_id' => [$flowRunSerialInfo->concurrent_node_id] ,'flow_serial' => [$flowRunSerialInfo->flow_serial] , 'user_last_step_flag' => [1]],
                    'order_by' => ["process_serial" => "desc" , 'host_flag' => 'desc' , 'user_run_type'=>'asc' ], // 根据分支线上倒序排
                    'returntype' => 'array',
                    "fields" =>  ['branch_serial' , 'process_serial' , 'flow_process' , 'flow_run_process_id' , 'user_run_type' ,'process_id'] //
                ]);
             $branchHasJudged = []; // 该分支是否判断
             foreach ($flowRunSameFlowSerial as $key => $value) {
                if ($value['process_id'] ==  $processId) {
                    continue; // 自己这个分支不做判断
                }
                if (!in_array($value['branch_serial'], $branchHasJudged) && ($value['user_run_type'] == 1  || $flowProcessnodeKeyInfo[$value['flow_process']]['merge'])) {
                     return false;
                }
                array_push($branchHasJudged, $value['branch_serial']);
             }
        }
        return true;

    }
}
