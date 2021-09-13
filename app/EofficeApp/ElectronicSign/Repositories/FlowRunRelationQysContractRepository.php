<?php
namespace App\EofficeApp\ElectronicSign\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\ElectronicSign\Entities\FlowRunRelationQysContractEntity;

class FlowRunRelationQysContractRepository extends BaseRepository
{
    public function __construct(FlowRunRelationQysContractEntity $entity)
    {
        parent::__construct($entity);
    }

    public function getDocumentIdByRunId($runId)
    {
        return $this->entity->where('runId', $runId)->pluck('documentId')->toArray();
    }

}
