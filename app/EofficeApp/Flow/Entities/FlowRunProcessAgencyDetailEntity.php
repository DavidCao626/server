<?php
namespace App\EofficeApp\Flow\Entities;

use App\EofficeApp\Base\BaseEntity;
/**
 * 流程运行过程委托详情实体
 *
 * @author lixuanxuan
 *
 * @since  2018-12-17 创建
 */
class FlowRunProcessAgencyDetailEntity extends BaseEntity
{

    /**
     * 表名
     *
     * @var string
     */
    public $table = 'flow_run_process_agency_detail';

    /**
     * 主键
     *
     * @var string
     */
    public $primaryKey = 'agency_detail_id';


    /**
     * 一条流程委托详情 flow_run_process_id 对应一个定义流程运行过程
     *
     * @return [type]                     [description]
     */
    function flowRunProcessAgencyDetailBelongsToFlowRunProcess()
    {
        return $this->belongsTo('App\EofficeApp\Flow\Entities\FlowRunProcessEntity','flow_run_process_id','flow_run_process_id');
    }

    /**
     * 执行模型是否自动维护时间戳.
     *
     * @var bool
     */
    public $timestamps = false;

    protected $fillable = ['flow_run_process_id', 'flow_agency_id', 'user_id','by_agency_id',"sort", "type"];

    /**
     * 流程委托人关联user表
     *
     * @return [type]                     [description]
     */
    function flowRunProcessAgencyDetailHasOneAgentUser()
    {
        return $this->hasOne('App\EofficeApp\User\Entities\UserEntity','user_id','by_agency_id');
    }


    /**
     * 流程委托的人关联user表
     *
     * @return [type]                     [description]
     */
    function flowRunProcessAgencyDetailHasOneUser()
    {
        return $this->hasOne('App\EofficeApp\User\Entities\UserEntity','user_id','user_id');
    }

}
