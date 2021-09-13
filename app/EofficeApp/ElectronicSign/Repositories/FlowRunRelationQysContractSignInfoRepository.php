<?php
namespace App\EofficeApp\ElectronicSign\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\ElectronicSign\Entities\FlowRunRelationQysContractSignInfoEntity;

class FlowRunRelationQysContractSignInfoRepository extends BaseRepository
{
    public function __construct(FlowRunRelationQysContractSignInfoEntity $entity)
    {
        parent::__construct($entity);
    }

    public function getByRunId($runId, $order = 'ASC')
    {
        return $this->entity->where('runId', $runId)->orderBy('serialNo', $order)->get();
    }
}
