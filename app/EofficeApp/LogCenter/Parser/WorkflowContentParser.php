<?php
namespace App\EofficeApp\LogCenter\Parser;
class WorkflowContentParser implements ParserInterface
{


    public function parseContentData(&$data)
    {
        //示例，具体内容解析还需要各个模块负责人进行处理
       try{
           if ($data['log_category'] == 'flow_run') {
              switch ($data['log_operate']) {
                case 'init_flow_run_seq':
                 $arrayData = json_decode($data['log_content'],true);
                 if (is_array($arrayData)) {
                    $run_id = $arrayData['run_id']?? '';
                    $run_seq = $arrayData['run_seq']?? '';
                    $data['log_content'] ="run_id:". $run_id.' , run_seq:'.strip_tags($run_seq) ;
                 }
                  break;
                default:
                  break;
              }

           } else if ($data['log_category'] == 'flow_design') {
              switch ($data['log_operate']) {
                case 'edit':
                $data['json_log_content'] = $data['log_content'];
                 $arrayData = json_decode($data['log_content'],true);
                 $title = $arrayData['title'] ?? '';
                 $operation = $arrayData['operation'] ?? "";
                 $data['log_content'] = $title.' '.$operation ;
                  break;
                default:
                  break;
              }
           }

       }catch (\Exception $e){
           \Log::info($e->getMessage());
       }

    }


}