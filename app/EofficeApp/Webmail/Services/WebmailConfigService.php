<?php


namespace App\EofficeApp\Webmail\Services;

use App\EofficeApp\Base\BaseService;

class WebmailConfigService extends BaseService
{
    public function __construct()
    {
        parent::__construct();
        $this->webmailShareConfigRepository = 'App\EofficeApp\Webmail\Repositories\WebmailShareConfigRepository';
        $this->webmailShareConfigRelationOutboxRepository = 'App\EofficeApp\Webmail\Repositories\WebmailShareConfigRelationOutboxRepository';
    }

    public function addShareConfig($data, $userId)
    {
        $data['creator'] = $userId;
        $shareConfig = array_intersect_key($data, array_flip(app($this->webmailShareConfigRepository)->getTableColumns()));;
        $outboxs = $data['outbox_id'] ?? [];
        $folders = $data['folder'] ?? [];
        if (!$outboxs || !$folders) {
            return ['code' => ['not_choose_account', 'webmail']];
        }
        $config =  app($this->webmailShareConfigRepository)->insertData($shareConfig);
        if ($config) {
            $relationOutbox = [];
            foreach($outboxs as $outbox) {
                foreach ($folders as $folder) {
                    $relationOutbox[] = [
                        'config_id' => $config->id,
                        'outbox_id' => $outbox,
                        'folder' => $folder
                    ];
                }
            }
            if ($relationOutbox) {
                app($this->webmailShareConfigRelationOutboxRepository)->insertMultipleData($relationOutbox);
            }
            return true;
        } else {
            return ['code' => ['save_failed', 'webmail']];
        }
    }

    public function editShareConfig($configId, $data, $userId)
    {
        $shareConfig = array_intersect_key($data, array_flip(app($this->webmailShareConfigRepository)->getTableColumns()));;
        $config = app($this->webmailShareConfigRepository)->updateData($shareConfig,['id' => [$configId]]);
        if ($config) {
            $outboxs = $data['outbox_id'] ?? [];
            $folders = $data['folder'] ?? [];
            $relationOutbox = [];
            if ($outboxs && $folders){
                foreach($outboxs as $outbox) {
                    foreach ($folders as $folder) {
                        $relationOutbox[] = [
                            'config_id' => $configId,
                            'outbox_id' => $outbox,
                            'folder' => $folder
                        ];
                    }
                }
                if ($relationOutbox) {
                    app($this->webmailShareConfigRelationOutboxRepository)->deleteByWhere(['config_id' => [$configId]]);
                    app($this->webmailShareConfigRelationOutboxRepository)->insertMultipleData($relationOutbox);
                }
            }
            return true;
        } else {
            return ['code' => ['edit_failed', 'webmail']];
        }
    }

    public function deleteShareConfig($configId, $data, $userId)
    {
        $res = app($this->webmailShareConfigRepository)->deleteById($configId);
        if ($res) {
            app($this->webmailShareConfigRelationOutboxRepository)->deleteByWhere(['config_id' => [$configId]]);
            return true;
        } else {
            return ['code' => ['delete_failed', 'webmail']];
        }
    }


    public function getShareConfigList($param, $userId)
    {
        $param = $this->parseParams($param);
        $param['search']['creator'] = [$userId];
        return $this->response(app($this->webmailShareConfigRepository), 'getNum', 'getList', $param);
    }

    public function getDetail($id)
    {
        $detail =  app($this->webmailShareConfigRepository)->getDetail($id);
        $purview = json_decode($detail->purview);
        $detail->all_staff = isset($purview->all_staff) ? $purview->all_staff : '';
        if($detail->all_staff != 1){
            $detail->user_id =  isset($purview->user_id) ? $purview->user_id : [];
            $detail->dept_id =  isset($purview->dept_id) ? $purview->dept_id : [];
            $detail->role_id =  isset($purview->role_id) ? $purview->role_id : [];
        }
        if ($detail) {
            $relationOutboxs = app($this->webmailShareConfigRelationOutboxRepository)->getByConfig($id);
            $detail->outbox_id = array_values(array_unique(array_column($relationOutboxs, 'outbox_id')));
            $folders = array_unique(array_column($relationOutboxs, 'folder'));
            $folderArray = [];
            foreach (['sent', 'inbox'] as $value) {
                $folderArray[$value] = 0;
                if (in_array($value, $folders)) {
                    $folderArray[$value] = 1;
                }
            }
            $detail->folder = $folderArray;
        }
        return $detail;
    }
}