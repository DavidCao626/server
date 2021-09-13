<?php
namespace App\EofficeApp\Meeting\Requests;

use App\EofficeApp\Base\Request;
use Illuminate\Contracts\Validation\Validator;
class MeetingRequest extends Request
{
    public function rules($request) 
    {  
		$rules = [
				'addEquipment'	=> [
		            'equipment_name'		=> "required"
		        ],
				'editEquipment'	=> [
		            'equipment_name'		=> "required"
		        ],
				'addRoom' => [
		            'room_name'		=> "required",
					'room_space'	=> "min:1|integer",
		        ],
				'editRoom' => [
		            'room_name'		=> "required",
					'room_space'	=> "min:1|integer",
		        ],
				'addMeeting' => [
		            'meeting_room_id'		=> "required",
					'meeting_subject'		=> "required",
					'meeting_begin_time'	=> 'required',
					'meeting_end_time'		=> 'required'
		        ],
				'editMeeting' => [
		            'meeting_room_id'		=> "required",
					'meeting_subject'		=> "required",
					'meeting_begin_time'	=> 'required',
					'meeting_end_time'		=> 'required'
		        ],
				'addMeetingRecord' =>  [
		            'meeting_apply_id'		=> "required",
					'record_content'		=> "required",
		        ],
				'editMeetingRecord' =>  [
		            'meeting_apply_id'		=> "required",
					'record_content'		=> "required",
		        ]
			];

        $function = explode("@", $request->route()[1]['uses'])[1];
        return $this->getRouteValidateRule($rules, $function);
    }
}
