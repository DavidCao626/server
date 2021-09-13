<?php
namespace App\EofficeApp\LogCenter\Repositories;
use DB;
use App\EofficeApp\LogCenter\Traits\LogTrait;
use App\EofficeApp\LogCenter\Traits\QueryTrait;
/**
 * Description of LogRepository
 *
 * @author lizhijun
 */

class LogRepository
{
    use LogTrait;
    use QueryTrait;
    private $defaultParams;
    public function __construct() {
        $this->defaultParams = [
            'fields' => ['*'],
            'page' => 0,
            'limit' => config('eoffice.pagesize', 15),
            'order_by' => ['log_time' => 'desc'],
            'search' => []
        ];
    }
    public function addLogData($moduleKey, $data)
    {
        return DB::table($this->makeLogTable($moduleKey))->insertGetId($data);
    }
    public function addChangeData($moduleKey, $data)
    {
        return DB::table($this->makeChangeTable($moduleKey))->insert($data);
    }
    
    public function getLogTotal($params, $moduleKey)
    {
        $params = array_merge($this->defaultParams, $params);
        return $this->wheres(DB::table($this->makeLogTable($moduleKey)),$params['search'])->count();
    }
    
    public function getLogList($params, $moduleKey)
    {
        $params = array_merge($this->defaultParams, $params);
        $query = $this->wheres(DB::table($this->makeLogTable($moduleKey))->select($params['fields']),$params['search']);
        $query = $this->orders($query, $params['order_by']);
        return $this->page($query, $params['page'], $params['limit'])->get();
    }
    public function getLogsByLogId($moduleKey, $logId, $fields = ['*'])
    {
        $query = DB::table($this->makeLogTable($moduleKey))->select($fields);
        if (is_array($logId)) {
            return $query->whereIn('log_id', $logId)->get();
        }
        return $query->where('log_id', $logId)->first();
    }
    public function getChangeDataByRelationId($moduleKey, $relationTable, $relationId, $fields = ['*'])
    {
        return $this->getChangeData($moduleKey, $relationTable, $fields, function($query) use($relationId) {
            return $query->where('relation_id', $relationId);
        });
    }

    public function getChangeDataByLogId($moduleKey, $relationTable, $logId, $fields = ['*'])
    {
        return $this->getChangeData($moduleKey, $relationTable, $fields, function($query) use($logId) {
            return $query->where('log_id', $logId);
        });
    }
    private function getChangeData($moduleKey, $relationTable, $fields, $handle)
    {
        $query = DB::table($this->makeChangeTable($moduleKey))
            ->select($fields)
            ->where('relation_table', $relationTable);
        
        $query = $handle($query);
        
        return $query->get();
    }

    public function getModuleUse($moduleKey , $time){
        $result = DB::table($this->makelogTable($moduleKey))
            ->whereBetween('log_time' , $time)
            ->count();
        return $result;
    }

    public function getLogCount($params, $moduleKey)
    {
        $params = array_merge($this->defaultParams, $params);
        return $this->wheres(DB::table($this->makeLogTable($moduleKey)),$params['search'])->count();

    }

    public function getLogs($params, $moduleKey)
    {
        $params = array_merge($this->defaultParams, $params);
        $query = $this->wheres(DB::table($this->makeLogTable($moduleKey))->select($params['fields']),$params['search']);
        $query = $this->orders($query, $params['order_by']);
        return $this->page($query, $params['page'], $params['limit'])->get()->toArray();
    }

    public function logViewCounts($moduleKey, $documentIds, $userId = '', $operate = [])
    {
        foreach ($documentIds as $k => &$v){
            $v = strval($v);
        }
        $query = DB::table($this->makeLogTable($moduleKey))
            ->selectRaw('relation_id,count(relation_id) as document_count')
            ->whereIn('relation_id',$documentIds);
        if($userId) {
            $query = $query->where('creator', $userId);
        }
        if(!empty($operate)) {
            $query = $query->whereIn('log_operate', $operate);
        }
        $logs = $query->groupBy('relation_id')->get();
        $logMap = [];
        if(count($logs) > 0){
            foreach ($logs as $log){
                $logMap[$log->relation_id] = $log->document_count;
            }
        }
        return $logMap;
    }

    public function documentLogs($moduleKey, $params)
    {
        $logs = DB::table($this->makelogTable($moduleKey))
            ->select($params['fields'])
            ->leftJoin('document_content', 'document_content.document_id', '=', 'eo_log_document.relation_id')
            ->where('eo_log_document.creator', $params['search']['creator'])
            ->whereBetween('eo_log_document.log_time', $params['search']['log_time'])
            ->whereIn('eo_log_document.log_operate', ['view', 'edit', 'add', 'delete'])
            ->get()->toArray();
        return $logs;
    }
}