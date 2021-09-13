<?php


namespace App\EofficeApp\Address\Services;


use App\EofficeApp\Base\BaseService;
use App\EofficeApp\FormModeling\Services\FormModelingService;

class AddressFlowOutSendService extends BaseService
{
    private $formModelingService;

    public function __construct(
        FormModelingService $formModelingService
    )
    {
        parent::__construct();
        $this->formModelingService = $formModelingService;
    }

    /**
     * @param $data
     * @return array|bool|string|\string[][]
     */
    public function flowOutCreatePrivateAddress($data)
    {
        if(isset($data['data'])){
            $data['data']['primary_6'] = isset($data['data']['primary_6']) && !empty($data['data']['primary_6']) ?$data['data']['primary_6']:date('Y-m-d  H:i:s');
        }
        return $this->formModelingService->addOutsendData($data['data'], $data['tableKey']);
    }

    /**
     * @param $data
     * @return array|bool|\string[][]
     */
    public function flowOutUpdatePrivateAddress($data)
    {
        return $this->formModelingService->editOutsendData($data, $data['tableKey']);
    }

    /**
     * @param $data
     * @return array|\string[][]
     */
    public function flowOutDeletePrivateAddress($data)
    {
        return $this->formModelingService->deleteOutsendData($data, $data['tableKey']);
    }

    /**
     * @param $data
     * @return array|bool|string|\string[][]
     */
    public function flowOutCreatePublicAddress($data)
    {
        if(isset($data['data'])){
            $data['data']['primary_6'] = isset($data['data']['primary_6']) && !empty($data['data']['primary_6']) ?$data['data']['primary_6']:date('Y-m-d  H:i:s');
        }
        return $this->formModelingService->addOutsendData($data['data'], $data['tableKey']);
    }

    /**
     * @param $data
     * @return array|bool|\string[][]
     */
    public function flowOutUpdatePublicAddress($data)
    {
        return $this->formModelingService->editOutsendData($data, $data['tableKey']);
    }

    /**
     * @param $data
     * @return array|\string[][]
     */
    public function flowOutDeletePublicAddress($data)
    {
        return $this->formModelingService->deleteOutsendData($data, $data['tableKey']);
    }
}
