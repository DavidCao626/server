<?php
namespace App\Utils\AppPush\Base;

interface PushInterface 
{
    public function sendMessage($message, $user, $type);
    
    public function validate();
}
