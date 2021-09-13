<?php
namespace App\EofficeApp\Calendar\Requests;

use App\EofficeApp\Base\Request;
use Illuminate\Contracts\Validation\Validator;
class CalendarRequest extends Request
{
    public function rules($request) 
    {  
        $rules = [
            'addCalendar' => [
                'calendar_content' => "required",
                'calendar_begin' => 'required',
                'calendar_end' => 'required',
                'handle_user' => 'required',
                'repeat_interval' => 'integer|min:1',
                'repeat_end_number' => 'integer|min:0',
            ],
            'editCalendar' => [
                'calendar_content' => "required",
                'calendar_begin' => 'required',
                'calendar_end' => 'required',
                'handle_user' => 'required',
                'repeat_interval' => 'integer|min:1',
                'repeat_end_number' => 'integer|min:0',
            ],
            'editAllCalendar' => [
                'calendar_content' => "required",
                'calendar_begin' => 'required',
                'calendar_end' => 'required',
                'handle_user' => 'required',
                'repeat_interval' => 'integer|min:1',
                'repeat_end_number' => 'integer|min:0',
            ],
            'createDiaryAttention' => [
                'attention_to_person' => 'required',
            ],
            'editDiaryAttention' => [
                'attention_status' => 'required|in:1,2,3',
            ],
        ];

        $function = explode("@", $request->route()[1]['uses'])[1];
        return $this->getRouteValidateRule($rules, $function);
    }
}
