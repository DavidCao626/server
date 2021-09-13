<?php

namespace App\EofficeApp\Vacation\Services;

use App\EofficeApp\Base\BaseService;
use App\EofficeApp\PersonnelFiles\Repositories\PersonnelFilesRepository;

class VacationStrategyService extends BaseService
{

    private $personnelFilesRepository;

    public function __construct(PersonnelFilesRepository $personnelFilesRepository)
    {
        parent::__construct();
        $this->personnelFilesRepository = $personnelFilesRepository;
    }

    /**
     * 获取人事当前里面的信息
     * @param $userIds
     */
    public function getProfileInfo($userIds)
    {
        if (!$userIds) {
            $userIds = [$userIds];
        }
        $params = [
            'search' => [
                'user_id' => [$userIds, 'in']
            ]
        ];
        $profiles = $this->personnelFilesRepository->getPersonnelFilesList($params);
        if (!$profiles) {
            return [];
        }
        if (isset($data['code'])) {
            return [];
        }
        $return = [];
        foreach ($profiles as $profile) {
            $item = [];
            //加入本单位时间
            if ($profile['join_date'] != '0000-00-00') {
                $item['join_date'] = $profile['join_date'];
            }
            //参加工作时间
            if ($profile['work_date'] != '0000-00-00') {
                $item['work_date'] = $profile['work_date'];
            }
            //出生日期
            if ($profile['birthday'] != '0000-00-00') {
                $item['birthday'] = $profile['birthday'];
            }
            $return[$profile['user_id']] = $item;
        }
        return $return;
    }
}