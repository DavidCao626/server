<?php
namespace App\EofficeApp\Document\Permissions;

class DocumentPermission
{
	private $documentFolderPurviewRepository;
	private $documentService;

	public $rules = [
		'batchMoveFolder' 	 		=> 'validMoveFolder',
		'migrateFolder'   	 		=> 'validMoveFolder',
		'documentLock'    	 		=> 'validDocumentManage',
		'applyUnlock'	  	 		=> 'validDocumentManage',
		'shareDocument'	  	 		=> 'shareDocument',
		'getRevertInfo'	     		=> 'validDocumentShow',
		'documentAttachment' 		=> 'validDocumentShow',
		'listLogs' 			 		=> 'validDocumentShow',
		'getPrvDocument'	 		=> 'validDocumentShow',
		'getNextDocument'	 		=> 'validDocumentShow',
        'getDocumentShareMember'    => 'validDocumentShow',
//		'migrateDocument'	        => 'validDocumentShow',
        'documentLockInfo'          => 'validDocumentShow',
        'hasDownPurview'            => 'validDocumentShow',
        'hasReplyPurview'           => 'validDocumentShow',
        'getPurview'                => 'validFolderManage',
        'getParentId'               => 'validFolderManage',
        'getChildrenId'             => 'validFolderManage',
	];

    public function __construct() {
    	$this->documentFolderPurviewRepository = 'App\EofficeApp\Document\Repositories\DocumentFolderPurviewRepository';
        $this->documentService 				   = 'App\EofficeApp\Document\Services\DocumentService';
        $this->documentContentRepository = 'App\EofficeApp\Document\Repositories\DocumentContentRepository';
    }

    public function delDocumentTags($own, $params) {
        if (isset($params['search']) && !is_array($params['search'])) {
            $params['search'] = json_decode($params['search'], true);
        }

        if (!isset($params['search']['document_id'][0])) {
            return false;
        }
    	return app($this->documentService)->hasDocumentManagePurview($params['search']['document_id'][0], $own);
    }

    public function addDocumentTags($own, $params) {
        if (!isset($params['document_id'])) {
            return false;
        }
        return app($this->documentService)->hasDocumentManagePurview($params['document_id'], $own);
    }

    public function batchSetModeAndTemplate($own, $params) {
    	if (!isset($params['folder_id']) || empty($params['folder_id'])) {
    		return false;
    	}

    	if ($own['user_id'] == 'admin') {
    		return true;
    	}

    	$folderIds = array_filter(explode(',', $params['folder_id']));
    	$manageIds = app($this->documentService)->getAllManageFolder($own);
    	if (!empty(array_diff($folderIds, $manageIds))) {
    		return ['code' => ['0x041012', 'document']];
    	}

    	return true;
    }

    public function validMoveFolder($own, $params, $urlData) {
    	if ((!isset($urlData['fromId']) || empty($urlData['fromId'])) && (!isset($urlData['fromIds']) || empty($urlData['fromIds']))) {
    		return false;
    	}

    	if (!isset($urlData['toId'])) {
    		return false;
    	}

    	if(isset($urlData['fromId']) && !empty($urlData['fromId'])){
            $fromIds = array_filter(explode(',', $urlData['fromId']));
        }else if(isset($urlData['fromIds']) && !empty($urlData['fromIds'])){
            $fromIds = array_filter(explode(',', $urlData['fromIds']));
        }else{
            return false;
        }
    	$toIds   = array_filter(explode(',', $urlData['toId']));
    	$manageIds = app($this->documentService)->getAllManageFolder($own);
    	if (!empty(array_diff($fromIds, $manageIds))) {
    		return ['code' => ['0x041012', 'document']];
    	}
    	if (!empty(array_diff($toIds, $manageIds))) {
    		return ['code' => ['0x041012', 'document']];
    	}
    	return true;
    }

    public function validDocumentManage($own, $params, $urlData) {
    	if (!isset($urlData['documentId']) || empty($urlData['documentId'])) {
    		return false;
    	}
        $documentInfo = app($this->documentContentRepository)->getDetail($urlData['documentId']);
        if (empty($documentInfo)) {
            return true;
        }
        
        if (!(app($this->documentService)->hasDocumentManagePurview($urlData['documentId'], $own) 
        || app($this->documentService)->hasDocumentEditPurview($urlData['documentId'], $own)
        || app($this->documentService)->hasCreatePurview($documentInfo->folder_id, $own))) {
			return ['code' => ['0x041018', 'document']];
		}

		return true;
    }

    public function shareDocument($own, $params, $urlData) {
    	if (!isset($urlData['documentId']) || empty($urlData['documentId'])) {
    		return false;
    	}
        $documentInfo = app($this->documentContentRepository)->getDetail($urlData['documentId']);
        if (empty($documentInfo)) {
            return true;
        }
    	if (!app($this->documentService)->hasDocumentManagePurview($urlData['documentId'], $own)) {
			return ['code' => ['0x041018', 'document']];
		}

		return true;
    }

    public function validDocumentShow($own, $params, $urlData) {
    	if (!isset($urlData['documentId']) || empty($urlData['documentId'])) {
    		return false;
        }
        
        $documentInfo = app($this->documentContentRepository)->getDetail($urlData['documentId']);
        if (empty($documentInfo)) {
            return true;
        }
    	if (!app($this->documentService)->hasDocumentShowPurview($urlData['documentId'], $own)) {
			return ['code' => ['0x041027', 'document']];
		}

		return true;
    }

    public function validFolderManage($own, $params, $urlData) {
    	if (!isset($urlData['folderId']) || empty($urlData['folderId'])) {
    		return false;
    	}

    	return app($this->documentService)->hasManagerPurview($urlData['folderId'], $own);
    }
}