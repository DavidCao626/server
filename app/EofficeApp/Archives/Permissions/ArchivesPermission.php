<?php
namespace App\EofficeApp\Archives\Permissions;
use DB;
class ArchivesPermission
{
    // 验证引擎会优先调用类里拥有的方法，如果没有则从该数组匹配找到对应的方法调用。

    public $rules = [
        'createArchivesFile' => 'editArchivesFile',
    ];
    public function __construct() 
    {
        $this->archivesVolumeRepository = 'App\EofficeApp\Archives\Repositories\ArchivesVolumeRepository';
    }

    /**
     * 验证修改卷库数据权限
     * @param type $own     // 当前用户信息
     * @param type $data    // 前端提交的数据或参数
     * @param type $urlData // restful api 上带的参数
     * @return boolean
     */
    public function editArchivesLibrary($own, $data, $urlData){
        $result = DB::table('archives_library')->select('*')->where('library_id',$data['library_id'])->first();
        if($result && $result->deleted_at == null){
            return true;
        }
        return false;
    }

    /**
     * 验证获取卷库数据日志权限
     * @param type $own     // 当前用户信息
     * @param type $data    // 前端提交的数据或参数
     * @param type $urlData // restful api 上带的参数
     * @return boolean
     */
    public function getIndexArchivesLibraryLog($own, $data, $urlData){
        $result = DB::table('archives_library')->select('*')->where('library_id',$urlData['libraryId'])->first();
        if($result && $result->deleted_at == null){
            return true;
        }
        return false;
    }

    /**
     * 验证案卷封卷权限
     * @param type $own     // 当前用户信息
     * @param type $data    // 前端提交的数据或参数
     * @param type $urlData // restful api 上带的参数
     * @return boolean
     */
    public function sealUpVolume($own, $data, $urlData){
        $result = DB::table('archives_file')->select('*')->where('volume_id',$urlData['volumeId'])->first();
        if($result){
            //判断是否已经封卷
            $resultVolume = DB::table('archives_volume')->select('*')->where('volume_id',$urlData['volumeId'])->first();
            if($resultVolume && $resultVolume->volume_status != 2){
                return true;
            }
        }
        return false;
    }

    /**
     * 验证案卷拆卷权限
     * @param type $own     // 当前用户信息
     * @param type $data    // 前端提交的数据或参数
     * @param type $urlData // restful api 上带的参数
     * @return boolean
     */
    public function sealOffVolume($own, $data, $urlData){
        //判断是否已经封卷
        $resultVolume = DB::table('archives_volume')->select('*')->where('volume_id',$urlData['volumeId'])->first();
        if($resultVolume && $resultVolume->volume_status == 2 ){
            return true;
        }
        return false;
    }

    /**
     * 验证修改档案文件权限
     * @param type $own     // 当前用户信息
     * @param type $data    // 前端提交的数据或参数
     * @param type $urlData // restful api 上带的参数
     * @return boolean
     */
    public function editArchivesFile($own, $data, $urlData){
        //编辑时验证是否已封卷(编辑接口)
        if (isset($urlData['id']) && $urlData['id']) {
            $fileData = DB::table('archives_file')->select('volume_id')->where('file_id',trim($urlData['id'],','))->first();
            if($fileData->volume_id){
                $resultVolume = DB::table('archives_volume')->select('*')->where('volume_id',$fileData->volume_id)->first();
                if($resultVolume && $resultVolume->volume_status == 2){
                    return false;
                }
            }
        }
        //判断是否已经封卷(添加、编辑接口)
        if (isset($data['volume_id']) && $data['volume_id']) {
            $volumeData = DB::table('archives_volume')->select('*')->where('volume_id',$data['volume_id'])->first();
            if($volumeData && $volumeData->volume_status == 2){
                return ['code' => ['error_edit','archives']];
            }
        }

        return true;
    }

    /**
     * 验证文件销毁权限
     * @param type $own     // 当前用户信息
     * @param type $data    // 前端提交的数据或参数
     * @param type $urlData // restful api 上带的参数
     * @return boolean
     */
     public function deleteArchivesFile($own, $data, $urlData){
         if(isset($data['destroy_date'])){
             $fileData = DB::table('archives_file')->select('volume_id')->where('file_id',trim($urlData['id'],','))->first();
             if($fileData->volume_id == 0) return true;
             if($fileData->volume_id){
                 $resultVolume = DB::table('archives_volume')->select('*')->where('volume_id',$fileData->volume_id)->first();
                 if(!$resultVolume) return false;
                 if($resultVolume->deleted_at) return false;
                 if($resultVolume->volume_status == 2) return false;
             }
             return true;
         }
     }
 
    /**
     * 验证修改案卷文件权限
     * @param type $own     // 当前用户信息
     * @param type $data    // 前端提交的数据或参数
     * @param type $urlData // restful api 上带的参数
     * @return boolean
     */
    public function editArchivesVolume($own, $data, $urlData){
        $filterData = app($this->archivesVolumeRepository)->getVolumeDetail($urlData['id']);
        if(!$filterData) return false;
        return true;
    }

    /**
     * 验证获取案卷数据日志权限
     * @param type $own     // 当前用户信息
     * @param type $data    // 前端提交的数据或参数
     * @param type $urlData // restful api 上带的参数
     * @return boolean
     */
    public function getIndexArchivesVolumeLog($own, $data, $urlData){
        $result = DB::table('archives_volume')->select('*')->where('volume_id',$urlData['volumeId'])->first();
        if($result && $result->deleted_at == null){
            return true;
        }
        return false;
    }

    /**
     * 验证获取文件数据日志权限
     * @param type $own     // 当前用户信息
     * @param type $data    // 前端提交的数据或参数
     * @param type $urlData // restful api 上带的参数
     * @return boolean
     */
    public function getIndexArchivesFileLog($own, $data, $urlData){
        $result = DB::table('archives_file')->select('*')->where('file_id',$urlData['fileId'])->first();
        if($result && $result->deleted_at == null){
            return true;
        }
        return false;
    }

    /**
     * 验证删除我的借阅权限
     * @param type $own     // 当前用户信息
     * @param type $data    // 前端提交的数据或参数
     * @param type $urlData // restful api 上带的参数
     * @return boolean
     */
    public function deleteArchivesBorrow($own, $data, $urlData){
        // 910 借阅记录菜单
        $menu = $own['menus']['menu'];
        $borrowIds = array_filter(explode(',', $urlData['id']));
        $result = DB::table('archives_borrow')->select('borrow_user_id')->whereIn('borrow_id',$borrowIds)->get()->toArray();
        if(!in_array(910,$menu)){
            if($result && is_array($result)){
                foreach ($result as $key => $vo){
                    if($vo->borrow_user_id != $own['user_id']){
                        return false;
                    }
                }
                return true;
            }
        }
        return true;
    }

    /**
     * 验证获取我的借阅记录权限
     * @param type $own     // 当前用户信息
     * @param type $data    // 前端提交的数据或参数
     * @param type $urlData // restful api 上带的参数
     * @return boolean
     */
    // public function getArchivesMyBorrow($own, $data, $urlData){
    //     $result = DB::table('archives_borrow')->select('borrow_user_id')->where('borrow_id',$urlData['id'])->first();
    //     if($result && $result->borrow_user_id == $own['user_id']) return true;
    //     return false;
    // }

    /**
     * 验证修改档案借阅(档案借阅审批)权限
     * @param type $own     // 当前用户信息
     * @param type $data    // 前端提交的数据或参数
     * @param type $urlData // restful api 上带的参数
     * @return boolean
     */
    //归还判断
    public function editArchivesBorrow($own, $data, $urlData){
        //处理重复审批
        //1-已批准 2-未批准 3-归还  4-收回
        //909   借阅审批
        //908   我的借阅
        $menu = $own['menus']['menu'];
        if(isset($data['borrow_status']) && $data['borrow_status'] != 3 && !in_array(909,$menu)){
            return false;
        }
        $result = DB::table('archives_borrow')->select('borrow_user_id','borrow_status')->where('borrow_id',$data['borrow_id'])->first();
        if(!$result) return false;
        switch ($data['borrow_status']){
            case 1:
                if($result->borrow_status != 0) return ['code' => ['error_p','archives']];
                break;
            case 2:
                if($result->borrow_status != 0) return ['code' => ['error_notp','archives']];
                break;
            case 3:
                if($result->borrow_status != 1) return ['code' => ['error_return','archives']];
                break;
            case 4:
                if(($result->borrow_status != 3) && ($result->borrow_status != 1)) return ['code' => ['error_recovery','archives']];
                break;
            default:
                return false;
        }
        if(isset($data['borrow_status']) && $data['borrow_status'] == 3){
            //如果只有我的审批菜单，没有我的借阅菜单，则不能归还操作(状态不能置为3)
            if(in_array(909,$menu) && !in_array(908,$menu)){
                return false;
            }
//            $result = DB::table('archives_borrow')->select('borrow_user_id')->where('borrow_id',$data['borrow_id'])->first();
//            if(!$result) return false;
            if(!in_array(909,$menu)){
                if($result->borrow_user_id == $own['user_id']){
                    return true;
                }
                return false;
            }
        }
        return true;
    }

    /**
     * 验证删除档案销毁权限
     * @param type $own     // 当前用户信息
     * @param type $data    // 前端提交的数据或参数
     * @param type $urlData // restful api 上带的参数
     * @return boolean
     */
    public function deleteArchivesDestroy($own, $data, $urlData){
        switch ($data['type']) {
            case 'volume':
                if(DB::table('archives_volume')->select('deleted_at')->where('volume_id',$urlData['id'])->first()) return true;
                return false;
                break;
            case 'file':
                if(DB::table('archives_file')->select('deleted_at')->where('file_id',$urlData['id'])->first()) return true;
                return false;
                break;
        }
    }

    /**
     * 验证恢复档案销毁权限
     * @param type $own     // 当前用户信息
     * @param type $data    // 前端提交的数据或参数
     * @param type $urlData // restful api 上带的参数
     * @return boolean
     */
    public function editArchivesDestroy($own, $data, $urlData){
        switch ($data['type']){
            case 'volume':
                if(DB::table('archives_volume')->select('deleted_at')->where('volume_id',$urlData['id'])->first()) return true;
                return false;
            break;
            case 'file':
                if(DB::table('archives_file')->select('deleted_at')->where('file_id',$urlData['id'])->first()) return true;
                return false;
                break;
        }
    }

    /**
     * 验证销毁档案详情权限
     * @param type $own     // 当前用户信息
     * @param type $data    // 前端提交的数据或参数
     * @param type $urlData // restful api 上带的参数
     * @return boolean
     */
    public function getArchivesDestroy($own, $data, $urlData){
        switch ($data['type']){
            case 'volume':
                $result = DB::table('archives_volume')->select('deleted_at')->where('volume_id',$urlData['id'])->first();
                if($result->deleted_at) return true;
                return false;
                break;
            case 'file':
                $result = DB::table('archives_file')->select('deleted_at')->where('file_id',$urlData['id'])->first();
                if($result->deleted_at) return true;
                return false;
                break;
        }
    }

}
