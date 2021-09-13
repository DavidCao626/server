<?php

namespace App\EofficeApp\Archives\Services\Traits;

use App\EofficeApp\Archives\Entities\ArchivesFileEntity;
use App\EofficeApp\Archives\Entities\ArchivesLibraryEntity;
use App\EofficeApp\Archives\Entities\ArchivesVolumeEntity;
use Illuminate\Support\Arr;
Trait ArchivesFlowOutTrait
{
    ##############library外发##################
    function flowOutSendToCreateLibrary($data, $own = null)
    {
        $res = $this->createLibrary($data);

        return $this->handFlowOutSendResult($res, ArchivesLibraryEntity::class);
    }

    function flowOutSendToUpdateLibrary($data)
    {
        $id = Arr::get($data, 'unique_id');
        $userId = Arr::get($data, 'current_user_id');
        $data = Arr::get($data, 'data', []);
        $res = $this->updateLibrary($data, $id, $userId);
        $res === true && $res = $id; // 成功情况下返回id

        return $this->handFlowOutSendResult($res, ArchivesLibraryEntity::class);
    }

    function flowOutSendToDeleteLibrary($data)
    {
        $id = Arr::get($data, 'unique_id');
        $userId = Arr::get($data, 'current_user_id');
        $res = $this->deleteLibrary($id, $userId);
        $res === true && $res = $id; // 成功情况下返回id

        return $this->handFlowOutSendResult($res, ArchivesLibraryEntity::class);
    }


    ##############voloumn外发##################
    function flowOutSendToCreateVolume($data, $own = null)
    {
        $res = $this->createVolume($data);

        return $this->handFlowOutSendResult($res, ArchivesVolumeEntity::class);
    }

    function flowOutSendToUpdateVolume($data)
    {
        $id = Arr::get($data, 'unique_id');
        $userId = Arr::get($data, 'current_user_id');
        $data = Arr::get($data, 'data', []);
        $res = $this->updateVolume($data, $id, $userId);
        $res === true && $res = $id; // 成功情况下返回id

        return $this->handFlowOutSendResult($res, ArchivesVolumeEntity::class);
    }

    function flowOutSendToDeleteVolume($data)
    {
        $id = Arr::get($data, 'unique_id');
        $userId = Arr::get($data, 'current_user_id');
        $res = $this->deleteVolume($id, '', $userId);
        $res === true && $res = $this->deleteDestroyed('volume', $id, $userId);
        $res === true && $res = $id; // 成功情况下返回id

        return $this->handFlowOutSendResult($res, ArchivesVolumeEntity::class);
    }


    ##############file外发##################
    function flowOutSendToCreateFile($data, $own = null)
    {
        $res = $this->createFile($data);

        return $this->handFlowOutSendResult($res, ArchivesFileEntity::class);
    }

    function flowOutSendToUpdateFile($data)
    {
        $id = Arr::get($data, 'unique_id');
        $userId = Arr::get($data, 'current_user_id');
        $data = Arr::get($data, 'data', []);
        $res = $this->updateFile($data, $id, $userId);
        $res === true && $res = $id; // 成功情况下返回id

        return $this->handFlowOutSendResult($res, ArchivesFileEntity::class);
    }

    function flowOutSendToDeleteFile($data)
    {
        $id = Arr::get($data, 'unique_id');
        $userId = Arr::get($data, 'current_user_id');
        $res = $this->deleteFile($id, '', $userId);
        $res === true && $res = $this->deleteDestroyed('file', $id, $userId);
        $res === true && $res = $id; // 成功情况下返回id

        return $this->handFlowOutSendResult($res, ArchivesFileEntity::class);
    }

}