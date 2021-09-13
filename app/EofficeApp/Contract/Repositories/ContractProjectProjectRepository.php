<?php
namespace App\EofficeApp\Contract\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Contract\Entities\ContractProjectProjectEntity;
use DB;

/**
 * 合同信息
 * @author linlm
 * @since  2017-12-13
 */
class ContractProjectProjectRepository extends BaseRepository
{
    const table = 'contract_t_project_project';

    public function __construct(ContractProjectProjectEntity $entity)
    {
        parent::__construct($entity);
    }




    public function getProjectData($params){
        $query = $this->entity->select('type',DB::raw('sum(money) as value'))->groupBy('type');
        if($params && isset($params['search'])){
            $query = $query->wheres($params['search']);
        }
        return $query->get()->toArray();
    }

    public function getProjectList($params){
        return $this->entity->select('*')->orderBy('money','desc')->groupBy('project_id')->get()->toArray();
    }

    public static function getContractProject(){
        return DB::table('contract_t_project')->groupBy('contract_t_id')->orderBy('created_at','desc')->get()->toArray();
    }

    public static function getUncollected(){
        return DB::table(self::table)->where('pay_time','!=','0000-00-00')->sum('money');
    }

    public static function getInvoice(){
        return DB::table(self::table)->where('invoice_time','!=','0000-00-00')->sum('money');
    }

    public function getProjectLists($input){
        $query = $this->entity->select('*');
        if($input && isset($input['search'])){
            $query = $query->wheres($input['search']);
        }
        return $query->get()->toArray();
    }

    public function getProjectPayLists($input,$pay_type){
        $query = $this->entity->select('*')->where($pay_type);
        if($input && isset($input['search'])){
            $query = $query->wheres($input['search']);
        }
        return $query->sum('money');
    }
}
