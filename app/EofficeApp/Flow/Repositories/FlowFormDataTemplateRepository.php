<?php

namespace App\EofficeApp\Flow\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Flow\Entities\FlowFormDataTemplateEntity;
use App\EofficeApp\Flow\Repositories\FlowOthersRepository;

class FlowFormDataTemplateRepository extends BaseRepository {

    public function __construct(FlowFormDataTemplateEntity $entity) {
    	$this->FlowOthersRepository = 'App\EofficeApp\Flow\Repositories\FlowOthersRepository';
        parent::__construct($entity);
    }

    //获得数据模板
	public function getDataTemplate($flowId,$list=false,$id=0,$name="",$type=null,$own=null, $del = NULL , $orderBy = null){
		if($list){
			$entity = $this->entity->where('flow_id',$flowId);
			if (!empty($orderBy)) {
				$orderBy = explode('|', $orderBy);
				$entity->orderBy($orderBy[0] ,$orderBy[1]);
			} else {
				$entity->orderBy('name','asc');
			}
			if(!is_null($type)){
				$entity->where('type',$type);
				if($type==1){
					$entity->orderBy('sort','desc');
					if(isset($own['user_id'])){
						$entity->where('user_id',$own['user_id']);
					}
					$flowInfo = app($this->FlowOthersRepository)->findFlowOthers($flowId);
					if($flowInfo['flow_show_user_template']==0){
						return [];
					}
				}
			}
			$data = $entity->get()->toArray();

			// 如果是删除表单模板数据，则不需要解析，直接返回即可
			if ($del != 'del') {
                foreach($data as $k => $v){
                    $dataTemplateTemp = !empty($v['data_template']) ? json_decode($v['data_template'],TRUE) : [];
//                if (!empty($dataTemplateTemp)) {
//                    foreach ($dataTemplateTemp as $dataKey => $dataValue) {
//                        if (strpos($dataKey, '_COUNTERSIGN')) {
//                            unset($dataTemplateTemp[$dataKey]);
//                        }
//                    }
//                }
                    //处理会签内容，将模板中的会签内容展示
                    $dataTemplateTempNew = $this->handleFlowFormTemplateCountersignDetail($dataTemplateTemp, $own);

                    $data[$k]['data_template'] = $dataTemplateTempNew;
                }
            }

			return $data;
		}else{
			$entity = $this->entity->where('flow_id',$flowId);//->where('status',1);
			if($id){
				$entity->where('id',$id);
			}
			if(!is_null($type)){
				$entity->where('type',$type);
			}
			//if(!empty($name)){
				//$entity->where("name", 'like', '%' . $name . '%');
			//}
			$data = $entity->orderBy('status', 'DESC')->get()->toArray();

            $template = !empty($data[0]['data_template']) ? json_decode($data[0]['data_template'],TRUE) : [];
//            if (!empty($template)) {
//                foreach ($template as $dataKey => $dataValue) {
//                    if (strpos($dataKey, '_COUNTERSIGN')) {
//                        unset($template[$dataKey]);
//                    }
//                }
//            }

            //处理会签内容，将模板中的会签内容展示
            $templateNew = $this->handleFlowFormTemplateCountersignDetail($template, $own);

            return $templateNew;
		}
	}

	//设置数据模板
	public function setDataTemplate($param, $own = null){
		$data[0] = $param;
		$flowId = $data[0]['flowId'];
		$info = $this->getDataTemplate($flowId,true, 0, '', null, $own, 'del');
		foreach($info as $v){
			if(isset($data[0]['remark'])&&$data[0]['remark']!=$v['remark']){
				$this->deleteByWhere(['flow_id'=>[$flowId],'remark'=>[$v['remark']],'type'=>[0]]);
			}
		}
		//$this->deleteByWhere(['flow_id'=>[$flowId]]);
		if(!empty($data)){
			foreach($data as $v){
				$status= isset($v['status'])?$v['status']:0;
				if(isset($v['data_template'])){
					if (is_array($v['data_template'])) {
						$v['data_template'] = json_encode($v['data_template']);
					}
					$item = ['flow_id' => $flowId,'data_template'=>$v['data_template'],'status'=>$status];
					if(isset($v['remark'])){
						$item['remark'] = $v['remark'];
					}
					if(isset($v['name'])){
						$item['name'] = $v['name'];
					}
					if(isset($v['type'])&&$v['type']==1){
						$item['type'] = $v['type'];
						if(isset($v['user_id'])){
							$item['user_id'] = $v['user_id'];
						}
					}
					$item['sort'] = $v['sort'] ?? 0;
					$this->insertData($item);
				}
			}
		}
	}
	//保存用户模板
	public function saveUserTemplate($param,$userInfo=[]){
		if(is_array($param)&&!empty($param)){
			foreach($param as $v){
				if($v['user_id'] == $userInfo['user_id']) {
					$this->updateData(['user_template_name' => $v['template_name'],'sort'=>$v['sort']], ['id' => [$v['id']]]);
				}
			}
		}
	}
	//删除用户模板
	public function deleteUserTemplate($id){
		return $this->deleteByWhere(["id" => [$id]]);
	}

    /**
     * 处理表单模板中的会签内容
     *
     * @author 张译心
     *
     * @param array $dataTemplateTemp
     * @param array  $own
     *
     * @since  2019-08-16 创建
     *
     * @return array  返回结果
     */
    public function handleFlowFormTemplateCountersignDetail($dataTemplateTemp, $own)
    {
        $countersignArr = [];//保存表单模板中的每个会签内容
        $countsOfEachCountersign = [];//记录表单模板每个会签控件中的填写次数，有可能有多个

        //将模板中保存的会签内容重定义后全部塞入同名的data单元
        foreach ($dataTemplateTemp as $dataKey => $dataValue) {
            if (strpos($dataKey, '_COUNTERSIGN')) {

                //截取data单元名称，保留DATA_n样式
                $countersignUnitNameTemp = substr($dataKey, 0, strpos($dataKey, '_COUNTERSIGN'));

                //计数，统计每个会签中的记录数,可能有多个
                if (isset($countsOfEachCountersign[$countersignUnitNameTemp])) {
                    $countsOfEachCountersign[$countersignUnitNameTemp]++;
                } else {
                    $countsOfEachCountersign[$countersignUnitNameTemp] = 0;
                }

                //会签人ID和name全部替换成当前用户
                $countersignArr[$countersignUnitNameTemp][$countsOfEachCountersign[$countersignUnitNameTemp]]['countersign_user']['user_id'] = $own['user_id'];
                $countersignArr[$countersignUnitNameTemp][$countsOfEachCountersign[$countersignUnitNameTemp]]['countersign_user']['user_name'] = $own['user_name'];

                //会签时间
                $countersignArr[$countersignUnitNameTemp][$countsOfEachCountersign[$countersignUnitNameTemp]]['countersign_time'] = $dataValue['countersign_time'] ?? date('Y-m-d H:i:s');

                //会签内容
                $countersignArr[$countersignUnitNameTemp][$countsOfEachCountersign[$countersignUnitNameTemp]]['countersign_content'] = $dataValue['countersign_content'] ?? "";

                //有此参数时，导入模板弹窗才会展示会签内容,且必须设置为空，否则在导入模板数据时会签控件会多展示一个多余的没有意义的历史记录
                $countersignArr[$countersignUnitNameTemp][$countsOfEachCountersign[$countersignUnitNameTemp]]['process_id'] = '';

                //原操作复用，注销当前countersign名称的单元
                unset($dataTemplateTemp[$dataKey]);
            }
        }

        //返回合并后的包含模板会签数据的新数组
        return array_merge($dataTemplateTemp, $countersignArr);
    }

}