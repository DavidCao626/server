<?php
namespace App\EofficeApp\Flow\Services;

use App\EofficeApp\Flow\Services\FlowBaseService;

/**
 * 流程控件service类，用来管理相关控件资源
 *
 * @since  2018-11-9 创建
 */
class FlowControlService extends FlowBaseService
{
    public function __construct(){
        parent::__construct();
    }

    /**
     * 控件收藏
     *
     * @author wz
     *
     *
     *
     * @return array
     */

    public function saveControlCollection( $data ,$user){
        $controls = [];
        $result = true;
        //  单个增加
        if (!empty($data) && isset($data['title']) ) {
                if (!isset($data['type']) || empty($data['type'])) {
                    return false;
                }
                $controls['type'] = $data['type'];
                $controls['title'] = $data['title'];
                if (isset($data['attribute']) && is_array($data['attribute']) && !empty($data['attribute'])) {
                    $controls['attribute'] = json_encode($data['attribute']);
                } else {
                    $controls['attribute'] = '';
                }
                $controls['user_id'] = $user['user_id'] ?? '';
                $result =   app($this->controlCollectionRepository)->insertData($controls);
                if ($result) {
                    return true;
                }
                return false;
        }
         $mulcontrols = [];
         $clearNum = 0 ;
        // 批量保存
        if (!empty($data) && is_array($data) && !isset($data['title'])) {
            foreach ($data as $key => $value) {
                if (!isset($value['title']) ||  empty($value['title'])) {
                    $clearNum++;
                    continue;
                }
                if (!isset($value['type']) ||  empty($value['type'])) {
                    $clearNum++;
                    continue;
                }
                $mulcontrols[$key]['type'] = $value['type'];
                $mulcontrols[$key]['title'] = $value['title'];
                if (isset($value['attribute']) && is_array($value['attribute']) && !empty($value['attribute'])) {
                    $mulcontrols[$key]['attribute'] = json_encode($value['attribute']);
                } else {
                    $mulcontrols[$key]['attribute'] = '';
                }
                $mulcontrols[$key]['user_id'] = $user['user_id'] ?? '';
            }
        }

        if (!empty($mulcontrols)) {
             app($this->controlCollectionRepository)->deleteByWhere(['id' =>[0 , '>']]);
             $result =   app($this->controlCollectionRepository)->insertMultipleData($mulcontrols);
        } else if (is_array($data) && $clearNum == count($data) && !isset($data['title'])){
            app($this->controlCollectionRepository)->deleteByWhere(['id' =>[0 , '>']]);
        }
        return $result;
    }

     /**
     * 控件收藏 -》列表
     *
     * @author wz
     * @return array
     */

    public function getControlCollectionList(){
            $res =  app($this->controlCollectionRepository)->getControlCollectionList(['fields' =>['type' , 'title' , 'attribute']]);
            return $res;
    }


}