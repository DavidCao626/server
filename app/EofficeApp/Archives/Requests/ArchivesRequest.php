<?php
namespace App\EofficeApp\Archives\Requests;

use App\EofficeApp\Base\Request;

class ArchivesRequest extends Request
{
    public $errorCode = '0x023002';

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules($request)
    {
        $rules = [
            'createArchivesLibrary'     => [
                'library_name'      => "max:150",
                'library_number'    => 'max:50',
            ],
            'editArchivesLibrary'     => [
                'library_name'      => "max:150",
                'library_number'    => 'max:50',
            ],
            'createArchivesVolume'      => [
                'volume_name'       => "required|max:150",
                'volume_number'     => 'required|max:50',
            ],
            'editArchivesVolume'      => [
                'volume_name'       => "required|max:150",
                'volume_number'     => 'required|max:50',
            ],
            'createArchivesFile'        => [
                'file_name'         => "required|max:150",
                'file_number'       => 'required|max:50',
            ],
            'editArchivesFile'        => [
/*                'file_name'         => "required|max:150",
                'file_number'       => 'required|max:50',*/
            ],
            'createArchivesAppraisal'   => [
                'appraisal_type'    => "required|in:volume,file",
                'appraisal_data_id' => "required|integer",
            ],
            'createArchivesBorrow'      => [
                'borrow_type'       => "required|in:volume,file",
                'borrow_start'      => "required|date",
                'borrow_end'        => "required|date",
            ],
            'editArchivesBorrowApprove' => [
                'borrow_status'    => "required|in:1,2,3,4"
            ],
        ];

        $function = explode("@", $request->route()[1]['uses'])[1];
        return $this->getRouteValidateRule($rules, $function);
    }
}
